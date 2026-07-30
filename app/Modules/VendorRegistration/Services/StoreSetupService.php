<?php
namespace VMP\Modules\VendorRegistration\Services;

use VMP\Modules\VendorRegistration\Repositories\VendorStoreRepositoryInterface;
use VMP\Modules\VendorRegistration\Repositories\VendorRequestRepositoryInterface;
use VMP\Modules\VendorRegistration\DTOs\StoreSetupDTO;

class StoreSetupService
{
    public function __construct(private VendorStoreRepositoryInterface $storesRepo, private VendorRequestRepositoryInterface $requestsRepo)
    {
    }

    private function hasProductsForVendor(int $vendorId): bool
    {
        global $wpdb;
        $posts_table = $wpdb->posts;
        $count = (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(ID) FROM {$posts_table} WHERE post_type = %s AND post_author = %d", 'product', $vendorId));
        return $count > 0;
    }

    /**
     * Setup store for vendor and mark setup completed.
     * Returns updated store object.
     */
    public function setup(int $vendorId, StoreSetupDTO $dto): object
    {
        $existing = $this->storesRepo->findByVendor($vendorId);
        $data = $dto->toArray();
        $data['vendor_id'] = $vendorId;

        // Ensure slug generation using SlugGeneratorService
        $slugService = new SlugGeneratorService($this->storesRepo);

        // Determine desired slug
        if (!empty($data['store_slug'])) {
            $desired = $data['store_slug'];
        } elseif (!empty($data['store_name'])) {
            $desired = $data['store_name'];
        } else {
            $desired = 'vendor-' . $vendorId;
        }

        $generatedSlug = $slugService->generateUnique($desired, $vendorId);
        $data['store_slug'] = $generatedSlug;

        if ($existing) {
            // if slug changed, ensure permissions and product checks
            if (!empty($data['store_slug']) && $data['store_slug'] !== $existing->store_slug) {
                // if vendor already has products, only admin can change slug
                if ($this->hasProductsForVendor($vendorId) && !current_user_can('manage_options')) {
                    throw new \RuntimeException('Cannot change store slug after products exist. Contact admin.');
                }

                // check for uniqueness again (edge case)
                $maybe = $this->storesRepo->findBySlug($data['store_slug']);
                if ($maybe && (int)$maybe->vendor_id !== $vendorId) {
                    // generate alternative unique slug
                    $data['store_slug'] = $slugService->generateUnique($data['store_slug'], $vendorId);
                }
            }

            $this->storesRepo->update((int)$existing->id, array_merge($data, ['setup_completed' => 1]));
            $store = $this->storesRepo->findByVendor($vendorId);
        } else {
            $data['store_slug'] = $generatedSlug;
            $data['setup_completed'] = 1;
            $data['is_active'] = 0;
            $this->storesRepo->create($data);
            $store = $this->storesRepo->findByVendor($vendorId);
        }

        // Update vendor request status to 'store_active'
        $request = $this->requestsRepo->findByUser($vendorId);
        if ($request) {
            $this->requestsRepo->updateStatus((int)$request->id, 'store_active', null);
        }

        return $store;
    }

    public function isSetupComplete(int $vendorId): bool
    {
        $store = $this->storesRepo->findByVendor($vendorId);
        return $store && (int)$store->setup_completed === 1;
    }
}
