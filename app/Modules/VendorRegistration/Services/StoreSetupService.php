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

    /**
     * Setup store for vendor and mark setup completed.
     * Returns updated store object.
     */
    public function setup(int $vendorId, StoreSetupDTO $dto): object
    {
        $existing = $this->storesRepo->findByVendor($vendorId);
        $data = $dto->toArray();
        $data['vendor_id'] = $vendorId;
        // Ensure slug
        if (empty($data['store_slug']) && !empty($data['store_name'])) {
            $data['store_slug'] = sanitize_title($data['store_name']);
        }
        // ensure unique slug
        $slug = $data['store_slug'] ?? ('vendor-' . $vendorId);

        if ($existing) {
            // if slug changed, ensure uniqueness
            if (!empty($data['store_slug']) && $data['store_slug'] !== $existing->store_slug) {
                $maybe = $this->storesRepo->findBySlug($data['store_slug']);
                if ($maybe && $maybe->vendor_id !== $vendorId) {
                    $data['store_slug'] = $data['store_slug'] . '-' . $vendorId;
                }
            }
            $this->storesRepo->update((int)$existing->id, array_merge($data, ['setup_completed' => 1]));
            $store = $this->storesRepo->findByVendor($vendorId);
        } else {
            $data['store_slug'] = $slug;
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
