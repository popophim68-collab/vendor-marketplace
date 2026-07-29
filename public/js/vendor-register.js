/**
 * Vendor Marketplace — Public JS (Multi-step Registration)
 * تسجيل البائعين متعدد الخطوات مع AJAX، التحقق، ورفع الصور
 */
(function ($) {
    'use strict';

    const VMPRegister = {
        // الحالة الحالية
        currentStep: 1,
        totalSteps: 3,
        isSubmitting: false,
        formData: {},
        slugCheckTimer: null,

        // تهيئة النموذج
        init: function () {
            this.$form = $('#vmp-register-form');
            this.$steps = this.$form.find('.vmp-step-content');
            this.$navItems = $('.vmp-progress-step');
            this.$lines = $('.vmp-progress-line');
            this.$submitBtn = $('#vmp_submit_btn');
            this.$successMsg = $('#vmp-success-message');
            this.$errorMsg = $('#vmp-error-message');
            this.$retryBtn = $('#vmp_retry_btn');
            this.$slugInput = $('#vmp_store_slug');
            this.$slugStatus = $('.vmp-slug-status');

            if (!this.$form.length) {
                return;
            }

            this.bindEvents();
            this.initSlugChecker();
            this.initPasswordStrength();
            this.initPasswordToggle();
            this.initPlanSelection();
            this.restoreFormData();
            this.updateStepUI();
        },

        // ربط الأحداث
        bindEvents: function () {
            const self = this;

            // أزرار التنقل
            this.$form.on('click', '.vmp-btn-next', function (e) {
                e.preventDefault();
                self.goToNextStep();
            });

            this.$form.on('click', '.vmp-btn-prev', function (e) {
                e.preventDefault();
                self.goToPrevStep();
            });

            // إرسال النموذج — فقط من الخطوة الأخيرة
            this.$form.on('submit', function (e) {
                e.preventDefault();
                if (self.currentStep === self.totalSteps) {
                    self.submitForm();
                } else {
                    // إذا ضغط Enter في خطوة سابقة، ننتقل للخطوة التالية
                    self.goToNextStep();
                }
            });

            // إعادة المحاولة
            this.$retryBtn.on('click', function () {
                self.$errorMsg.hide();
                self.$form.show();
                $('.vmp-progress-steps').show();
            });

            // التحقق الفوري للحقول المطلوبة
            this.$form.on('input change', 'input[required], select[required], textarea[required]', function () {
                $(this).removeClass('vmp-input-error');
                self.clearFieldError($(this).attr('name'));
            });

            // تأكيد كلمة المرور
            this.$form.on('input', 'input[name="user_pass_confirm"]', function () {
                const pass = $('input[name="user_pass"]').val();
                const confirm = $(this).val();
                if (pass && confirm && pass !== confirm) {
                    self.showFieldError('user_pass_confirm', vmpRegisterData.strings.passwordMismatch);
                } else {
                    self.clearFieldError('user_pass_confirm');
                }

            // تحديث full_name تلقائياً عند تغيير first_name أو last_name
            this.$form.on("input", 'input[name="first_name"], input[name="last_name"]', function () {
                const first = $('input[name="first_name"]').val() || "";
                const last = $('input[name="last_name"]').val() || "";
                const full = (first + " " + last).trim();
                $('input[name="full_name"]').val(full);
            });
            // Auto-save form data to sessionStorage
            this.$form.on('input change', 'input, select, textarea', function () {
                self.saveFormData();
            });
        },

        // الانتقال للخطوة التالية
        goToNextStep: function () {
            if (!this.validateCurrentStep()) {
                return;
            }

            if (this.currentStep >= this.totalSteps) {
                return;
            }

            this.currentStep++;
            this.updateStepUI();
            this.saveFormData();
            this.scrollToForm();
        },

        // الانتقال للخطوة السابقة
        goToPrevStep: function () {
            if (this.currentStep <= 1) {
                return;
            }

            this.currentStep--;
            this.updateStepUI();
            this.scrollToForm();
        },

        // تحديث واجهة الخطوات
        updateStepUI: function () {
            // تحديث محتوى الخطوات
            this.$steps.removeClass('active').hide();
            this.$steps.filter('[data-step="' + this.currentStep + '"]').addClass('active').show();

            // تحديث مؤشرات التقدم
            this.$navItems.removeClass('active completed');
            this.$lines.removeClass('completed');

            const self = this; // <-- تعريف self هنا للدوال الداخلية
            this.$navItems.each(function (index) {
                const step = index + 1;
                const $item = $(this);
                if (step < self.currentStep) {
                    $item.addClass('completed');
                } else if (step === self.currentStep) {
                    $item.addClass('active');
                }
            });

            this.$lines.each(function (index) {
                if (index < self.currentStep - 1) {
                    $(this).addClass('completed');
                }
            });

            // تحديث الحقل المخفي
            $('#vmp_current_step').val(this.currentStep);

            // تحديث زر الإرسال
            const $submitBtn = this.$form.find('.vmp-btn-submit');
            if (this.currentStep === this.totalSteps) {
                $submitBtn.show();
                this.$form.find('.vmp-btn-next').hide();
            } else {
                $submitBtn.hide();
                this.$form.find('.vmp-btn-next').show();
            }
        },

        // التحقق من صحة الخطوة الحالية
        validateCurrentStep: function () {
            const $currentStep = this.$steps.filter('[data-step="' + this.currentStep + '"]');
            let isValid = true;
            let firstError = null;

            const self = this; // <-- تعريف self هنا للدوال الداخلية

            $currentStep.find('input[required], select[required], textarea[required]').each(function () {
                const $field = $(this);
                const value = $field.val() ? $field.val().toString().trim() : '';
                const name = $field.attr('name');

                if (!value) {
                    isValid = false;
                    $field.addClass('vmp-input-error');
                    if (!firstError) firstError = $field;
                } else {
                    $field.removeClass('vmp-input-error');
                }

                // التحقق الخاص بالبريد الإلكتروني
                if (name === 'user_email' && value && !self.isValidEmail(value)) {
                    isValid = false;
                    self.showFieldError(name, 'البريد الإلكتروني غير صحيح');
                    if (!firstError) firstError = $field;
                }

                // التحقق الخاص بالرقم (slug)
                if (name === 'store_slug' && value && !self.isValidSlug(value)) {
                    isValid = false;
                    self.showFieldError(name, 'استخدم أحرفاً إنجليزية صغيرة وأرقام وشرطات فقط');
                    if (!firstError) firstError = $field;
                }
            });

            // التحقق الخاص بالخطوة 1: تأكيد كلمة المرور
            if (this.currentStep === 1 && !vmpRegisterData.isLoggedIn) {
                const pass = $currentStep.find('input[name="user_pass"]').val();
                const confirm = $currentStep.find('input[name="user_pass_confirm"]').val();
                if (pass && confirm && pass !== confirm) {
                    isValid = false;
                    self.showFieldError('user_pass_confirm', vmpRegisterData.strings.passwordMismatch);
                    if (!firstError) firstError = $currentStep.find('input[name="user_pass_confirm"]');
                }
                // التحقق من قوة كلمة المرور
                if (pass && pass.length < 8) {
                    isValid = false;
                    self.showFieldError('user_pass', vmpRegisterData.strings.passwordWeak);
                    if (!firstError) firstError = $currentStep.find('input[name="user_pass"]');
                }
            }

            // التحقق الخاص بالخطوة 2: قبول الشروط
            if (this.currentStep === 2) {
                const terms = $currentStep.find('input[name="terms_accepted"]').is(':checked');
                if (!terms) {
                    isValid = false;
                    self.showFieldError('terms_accepted', 'يجب الموافقة على الأحكام والشروط');
                    if (!firstError) firstError = $currentStep.find('input[name="terms_accepted"]');
                }
            }

            // التحقق الخاص بالخطوة 3: اختيار خطة
            if (this.currentStep === 3) {
                const plan = $currentStep.find('input[name="plan_id"]:checked').val();
                if (!plan || plan === '0') {
                    // إذا لم تكن هناك خطط، نتخطى هذا التحقق
                    if ($currentStep.find('.vmp-plan-card').length > 0) {
                        isValid = false;
                        if (!firstError) firstError = $currentStep.find('.vmp-plan-card').first();
                    }
                }
            }

            if (!isValid && firstError) {
                firstError.focus();
            }

            return isValid;
        },

        // التحقق من صحة البريد الإلكتروني
        isValidEmail: function (email) {
            return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
        },

        // التحقق من صحة السلاگ
        isValidSlug: function (slug) {
            return /^[a-z0-9-]+$/.test(slug);
        },

        // إظهار خطأ حقل
        showFieldError: function (fieldName, message) {
            const $field = this.$form.find('[name="' + fieldName + '"]').first();
            $field.addClass('vmp-input-error');
            
            // إزالة رسالة الخطأ القديمة
            $field.siblings('.vmp-field-error').remove();
            
            // إضافة رسالة الخطأ الجديدة
            $field.after('<span class="vmp-field-error">' + message + '</span>');
        },

        // إزالة خطأ حقل
        clearFieldError: function (fieldName) {
            const $field = this.$form.find('[name="' + fieldName + '"]').first();
            $field.removeClass('vmp-input-error');
            $field.siblings('.vmp-field-error').remove();
        },

        // التحقق من توفر رابط المتجر (slug)
        initSlugChecker: function () {
            const self = this;
            
            this.$slugInput.on('input', function () {
                clearTimeout(self.slugCheckTimer);
                const slug = $(this).val().toLowerCase().replace(/[^a-z0-9-]/g, '');
                $(this).val(slug);

                if (slug.length < 3) {
                    self.$slugStatus.hide().removeClass('available taken');
                    return;
                }

                self.$slugStatus.text(vmpRegisterData.strings.checking).removeClass('available taken').show();

                self.slugCheckTimer = setTimeout(function () {
                    self.checkSlugAvailability(slug);
                }, 500);
            });
        },

        checkSlugAvailability: function (slug) {
            const self = this;
            
            $.post(vmpRegisterData.ajaxUrl, {
                action: 'vmp_check_store_slug',
                slug: slug,
                nonce: vmpRegisterData.nonce
            }, function (res) {
                if (res.success) {
                    if (res.data.available) {
                        self.$slugStatus.text(vmpRegisterData.strings.slugAvailable).removeClass('taken').addClass('available');
                        self.clearFieldError('store_slug');
                    } else {
                        self.$slugStatus.text(vmpRegisterData.strings.slugTaken).removeClass('available').addClass('taken');
                        self.showFieldError('store_slug', vmpRegisterData.strings.slugTaken);
                    }
                }
            });
        },

        // مقياس قوة كلمة المرور
        initPasswordStrength: function () {
            const self = this;
            const $passField = $('input[name="user_pass"]');
            const $strength = $('.vmp-password-strength');

            $passField.on('input', function () {
                const pass = $(this).val();
                if (!pass) {
                    $strength.hide().removeClass('weak medium strong').text('');
                    return;
                }

                let score = 0;
                if (pass.length >= 8) score++;
                if (/[A-Z]/.test(pass)) score++;
                if (/[a-z]/.test(pass)) score++;
                if (/[0-9]/.test(pass)) score++;
                if (/[^A-Za-z0-9]/.test(pass)) score++;

                $strength.show();
                if (score <= 2) {
                    $strength.removeClass('medium strong').addClass('weak').text(vmpRegisterData.strings.passwordWeak);
                } else if (score <= 4) {
                    $strength.removeClass('weak strong').addClass('medium').text(vmpRegisterData.strings.passwordMedium);
                } else {
                    $strength.removeClass('weak medium').addClass('strong').text(vmpRegisterData.strings.passwordStrong);
                }
            });
        },

        // إظهار/إخفاء كلمة المرور
        initPasswordToggle: function () {
            $(document).on('click', '.vmp-toggle-password', function () {
                const $btn = $(this);
                const $input = $btn.siblings('input[type="password"], input[type="text"]');
                const $icon = $btn.find('.dashicons');

                if ($input.attr('type') === 'password') {
                    $input.attr('type', 'text');
                    $icon.removeClass('dashicons-visibility').addClass('dashicons-hidden');
                } else {
                    $input.attr('type', 'password');
                    $icon.removeClass('dashicons-hidden').addClass('dashicons-visibility');
                }
            });
        },

        // اختيار الخطة
        initPlanSelection: function () {
            const self = this;
            
            $(document).on('click', '.vmp-plan-card', function (e) {
                if ($(e.target).is('input[type="radio"]')) return;
                
                const $card = $(this);
                const $radio = $card.find('input[type="radio"]');
                
                $('.vmp-plan-card').removeClass('selected');
                $card.addClass('selected');
                $radio.prop('checked', true).trigger('change');
            });

            $(document).on('change', 'input[name="plan_id"]', function () {
                const $card = $(this).closest('.vmp-plan-card');
                $('.vmp-plan-card').removeClass('selected');
                if ($(this).is(':checked')) {
                    $card.addClass('selected');
                }
            });

            // دعم لوحة المفاتيح
            $(document).on('keydown', '.vmp-plan-card', function (e) {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    $(this).click();
                }
            });
        },

        // حفظ بيانات النموذج في sessionStorage
        saveFormData: function () {
            const data = this.$form.serializeArray();
            const formData = {};
            data.forEach(function (field) {
                formData[field.name] = field.value;
            });
            try {
                sessionStorage.setItem('vmp_register_form', JSON.stringify(formData));
            } catch (e) {
                console.warn('Failed to save form data:', e);
            }
        },

        // استعادة بيانات النموذج
        restoreFormData: function () {
            try {
                const saved = sessionStorage.getItem('vmp_register_form');
                if (saved) {
                    const data = JSON.parse(saved);
                    Object.keys(data).forEach(function (key) {
                        const $field = this.$form.find('[name="' + key + '"]');
                        if ($field.length) {
                            if ($field.attr('type') === 'radio' || $field.attr('type') === 'checkbox') {
                                $field.filter('[value="' + data[key] + '"]').prop('checked', true).trigger('change');
                            } else {
                                $field.val(data[key]).trigger('input');
                            }
                        }
                    }, this);
                    this.formData = data;
                }
            } catch (e) {
                console.warn('Failed to restore form data:', e);
            }
        },

        // مسح البيانات المحفوظة
        clearFormData: function () {
            try {
                sessionStorage.removeItem('vmp_register_form');
            } catch (e) {
                console.warn('Failed to clear form data:', e);
            }
        },

        // التمرير إلى النموذج
        scrollToForm: function () {
            $('html, body').animate({ scrollTop: this.$form.offset().top - 80 }, 300);
        },

        // إرسال النموذج عبر AJAX
        submitForm: function () {
            if (this.isSubmitting) return;
            
            if (!this.validateCurrentStep()) {
                return;
            }

            this.isSubmitting = true;
            const self = this;

            // تحديث واجهة الزر
            const $btnText = this.$submitBtn.find('.vmp-btn-text');
            const $btnLoading = this.$submitBtn.find('.vmp-btn-loading');
            $btnText.hide();
            $btnLoading.show();
            this.$submitBtn.prop('disabled', true);

            // جمع البيانات
            const formData = new FormData(this.$form[0]);
            formData.append('action', 'vmp_vendor_register');

            $.ajax({
                url: vmpRegisterData.ajaxUrl,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                dataType: 'json',
                success: function (res) {
                    self.handleSubmitResponse(res);
                },
                error: function (xhr) {
                    let message = vmpRegisterData.strings.error;
                    if (xhr.responseJSON && xhr.responseJSON.data) {
                        if (xhr.responseJSON.data.message) {
                            message = xhr.responseJSON.data.message;
                        } else if (xhr.responseJSON.data.errors && xhr.responseJSON.data.errors.length) {
                            message = xhr.responseJSON.data.errors.join('<br>');
                        }
                    }
                    self.showError(message);
                },
                complete: function () {
                    self.isSubmitting = false;
                    $btnText.show();
                    $btnLoading.hide();
                    self.$submitBtn.prop('disabled', false);
                }
            });
        },

        // معالجة رد الإرسال
        handleSubmitResponse: function (res) {
            if (res.success) {
                this.clearFormData();
                this.showSuccess(res.data);
            } else {
                // التعامل مع أخطاء التحقق
                if (res.data.errors && res.data.errors.length) {
                    const self = this;
                    res.data.errors.forEach(function (error) {
                        // محاولة استخراج اسم الحقل من الرسالة
                        if (error.field) {
                            self.showFieldError(error.field, error.message);
                        }
                    });
                    if (res.data.step) {
                        this.currentStep = res.data.step;
                        this.updateStepUI();
                    }
                }
                this.showError(res.data.message || vmpRegisterData.strings.error);
            }
        },

        // إظهار رسالة النجاح
        showSuccess: function (data) {
            this.$form.hide();
            $('.vmp-progress-steps').hide();
            
            const $successText = $('#vmp_success_text');
            const $successActions = $('#vmp_success_actions');
            
            if (data.message) {
                $successText.text(data.message);
            }
            
            if (data.redirect) {
                $successActions.html('<a href="' + data.redirect + '" class="vmp-btn vmp-btn-primary" style="padding: 14px 32px; font-size: 16px;">' + 
                    '<span class="dashicons dashicons-admin-generic" style="margin-right: 8px;"></span>الذهاب إلى لوحة التحكم</a>');
                
                // إعادة توجيه تلقائية بعد 3 ثوانٍ
                setTimeout(function () {
                    window.location.href = data.redirect;
                }, 3000);
            } else if (data.request_id) {
                $successText.append(' رقم طلبك: #' + data.request_id);
            }

            this.$successMsg.show();
            
            // التمرير للرسالة
            $('html, body').animate({ scrollTop: this.$successMsg.offset().top - 80 }, 300);
        },

        // إظهار رسالة الخطأ
        showError: function (message) {
            this.$form.hide();
            $('.vmp-progress-steps').hide();
            $('#vmp_error_text').html(message);
            this.$errorMsg.show();
            $('html, body').animate({ scrollTop: this.$errorMsg.offset().top - 80 }, 300);
        }
    };

    // تهيئة عند جاهزية المستند
    $(function () {
        VMPRegister.init();
    });

    // كشف عن الكائن للنطاق العام (للتصحيح)
    window.VMPRegister = VMPRegister;

})(jQuery);
