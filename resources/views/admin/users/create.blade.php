@extends('layouts.admin')

@section('title', 'Create Rescuer')
@section('header', 'Create New Rescuer')
@section('subtitle', 'Add a new rescuer to the system')

@section('content')
<!-- Error Messages -->
@if($errors->any())
<div class="card p-4 mb-6 border border-accent-900 bg-accent-900 bg-opacity-20">
    <div class="flex items-start">
        <i class="fas fa-exclamation-circle text-accent-400 mt-0.5 mr-3"></i>
        <div class="flex-1">
            <p class="text-sm font-semibold text-accent-300 mb-2">Please fix the following errors:</p>
            <ul class="text-xs text-accent-400 space-y-1">
                @foreach($errors->all() as $error)
                    <li>• {{ $error }}</li>
                @endforeach
            </ul>
        </div>
    </div>
</div>
@endif

@if(session('error'))
<div class="card p-4 mb-6 border border-accent-900 bg-accent-900 bg-opacity-20">
    <div class="flex items-center">
        <i class="fas fa-exclamation-circle text-accent-400 mr-3"></i>
        <p class="text-sm text-accent-300">{{ session('error') }}</p>
    </div>
</div>
@endif

<div class="max-w-3xl mx-auto">
    <!-- Info Banner -->
    <div class="card p-4 mb-6 border border-secondary-900 bg-secondary-900 bg-opacity-20">
        <div class="flex items-start">
            <i class="fas fa-info-circle text-secondary-400 mt-0.5 mr-3"></i>
            <p class="text-sm text-secondary-300">
                All users created here will be assigned the <span class="font-semibold">Rescuer</span> role automatically and can login via the mobile app.
            </p>
        </div>
    </div>

    <form id="createUserForm" action="{{ route('admin.users.store') }}" method="POST" novalidate>
        @csrf

        <!-- Account Information -->
        <div class="card p-6 mb-6 border border-primary-700">
            <h3 class="text-sm font-semibold text-primary-100 mb-4">Account Information</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="md:col-span-2">
                    <label class="block text-xs text-primary-400 mb-2">Full Name *</label>
                    <input type="text" 
                           name="full_name" 
                           id="full_name"
                           value="{{ session('clear_form') ? '' : old('full_name') }}" 
                           class="input-field text-sm @error('full_name') border-accent-400 @enderror" 
                           required
                           maxlength="255">
                    @error('full_name')
                        <p class="text-accent-400 text-xs mt-1">{{ $message }}</p>
                    @enderror
                    <p class="text-xs text-primary-500 mt-1 error-message hidden" id="full_name_error"></p>
                </div>

                <div>
                    <label class="block text-xs text-primary-400 mb-2">Email Address *</label>
                    <input type="email" 
                           name="email" 
                           id="email"
                           value="{{ session('clear_form') ? '' : old('email') }}" 
                           class="input-field text-sm @error('email') border-accent-400 @enderror" 
                           placeholder="rescuer@example.com" 
                           required>
                    @error('email')
                        <p class="text-accent-400 text-xs mt-1">{{ $message }}</p>
                    @enderror
                    <p class="text-xs text-primary-500 mt-1">
                        <i class="fas fa-mobile-alt mr-1"></i>Used for mobile app login
                    </p>
                    <p class="text-xs text-accent-400 mt-1 error-message hidden" id="email_error"></p>
                </div>

                <div>
                    <label class="block text-xs text-primary-400 mb-2">Phone Number</label>
                    <input type="tel" 
                           name="phone" 
                           id="phone"
                           value="{{ session('clear_form') ? '' : old('phone') }}" 
                           class="input-field text-sm @error('phone') border-accent-400 @enderror" 
                           placeholder="09123456789"
                           maxlength="11"
                           pattern="[0-9]{11}"
                           inputmode="numeric">
                    @error('phone')
                        <p class="text-accent-400 text-xs mt-1">{{ $message }}</p>
                    @enderror
                    <p class="text-xs text-primary-500 mt-1">
                        <i class="fas fa-phone mr-1"></i>11 digits (e.g., 09123456789)
                    </p>
                </div>
            </div>
        </div>

        <!-- Security -->
        <div class="card p-6 mb-6 border border-primary-700">
            <h3 class="text-sm font-semibold text-primary-100 mb-4">
                <i class="fas fa-lock text-secondary-400 mr-2"></i>Security
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs text-primary-400 mb-2">Password *</label>
                    <input type="password" 
                           name="password" 
                           id="password"
                           class="input-field text-sm @error('password') border-accent-400 @enderror" 
                           placeholder="Minimum 8 characters" 
                           required 
                           minlength="8">
                    @error('password')
                        <p class="text-accent-400 text-xs mt-1">{{ $message }}</p>
                    @enderror
                    <p class="text-xs text-primary-500 mt-1">
                        <i class="fas fa-shield-alt mr-1"></i>Minimum 8 characters
                    </p>
                    <p class="text-xs text-accent-400 mt-1 error-message hidden" id="password_error"></p>
                </div>

                <div>
                    <label class="block text-xs text-primary-400 mb-2">Confirm Password *</label>
                    <input type="password" 
                           name="password_confirmation" 
                           id="password_confirmation"
                           class="input-field text-sm" 
                           placeholder="Re-enter password" 
                           required 
                           minlength="8">
                    <p class="text-xs text-accent-400 mt-1 error-message hidden" id="password_confirmation_error"></p>
                </div>
            </div>
        </div>

        <!-- Actions -->
        <div class="flex gap-3">
            <button type="submit" id="submitBtn" class="bg-secondary-400 hover:bg-secondary-500 text-primary-950 px-6 py-2.5 rounded-lg transition text-sm font-medium disabled:opacity-50 disabled:cursor-not-allowed">
                <i class="fas fa-user-plus mr-2"></i><span id="btnText">Create Rescuer</span>
            </button>
            <a href="{{ route('admin.users.index') }}" class="bg-primary-700 hover:bg-primary-600 text-primary-200 px-6 py-2.5 rounded-lg transition text-sm">
                <i class="fas fa-times mr-2"></i>Cancel
            </a>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
    const form = document.getElementById('createUserForm');
    const submitBtn = document.getElementById('submitBtn');
    const btnText = document.getElementById('btnText');

    // Check if form should be cleared (after successful creation)
    const shouldClearForm = {{ session('clear_form') ? 'true' : 'false' }};
    const successMessage = @json(session('success'));
    
    if (shouldClearForm && successMessage) {
        // Clear all form fields
        form.reset();
        
        // Show success message
        Swal.fire({
            icon: 'success',
            title: 'Success!',
            text: successMessage,
            timer: 3000,
            showConfirmButton: false,
            background: '#0f172a',
            color: '#e2e8f0',
            iconColor: '#4ECDC4'
        });
    }

    // Client-side validation
    function validateForm() {
        let isValid = true;
        
        // Clear previous errors
        document.querySelectorAll('.error-message').forEach(el => {
            el.classList.add('hidden');
            el.textContent = '';
        });
        
        // Validate full name
        const fullName = document.getElementById('full_name');
        if (!fullName.value.trim()) {
            showError('full_name', 'Full name is required');
            isValid = false;
        }
        
        // Validate email
        const email = document.getElementById('email');
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!email.value.trim()) {
            showError('email', 'Email is required');
            isValid = false;
        } else if (!emailRegex.test(email.value)) {
            showError('email', 'Please enter a valid email address');
            isValid = false;
        }
        
        // Validate phone (optional, but if provided must be 11 digits)
        const phone = document.getElementById('phone');
        if (phone.value && phone.value.length !== 11) {
            showError('phone', 'Phone number must be exactly 11 digits');
            isValid = false;
        }
        
        // Validate password
        const password = document.getElementById('password');
        if (!password.value) {
            showError('password', 'Password is required');
            isValid = false;
        } else if (password.value.length < 8) {
            showError('password', 'Password must be at least 8 characters');
            isValid = false;
        }
        
        // Validate password confirmation
        const passwordConfirmation = document.getElementById('password_confirmation');
        if (!passwordConfirmation.value) {
            showError('password_confirmation', 'Please confirm your password');
            isValid = false;
        } else if (password.value !== passwordConfirmation.value) {
            showError('password_confirmation', 'Passwords do not match');
            isValid = false;
        }
        
        return isValid;
    }
    
    function showError(fieldId, message) {
        const errorEl = document.getElementById(fieldId + '_error');
        if (errorEl) {
            errorEl.textContent = message;
            errorEl.classList.remove('hidden');
        }
        const field = document.getElementById(fieldId);
        if (field) {
            field.classList.add('border-accent-400');
        }
    }
    
    // Remove error styling on input
    form.querySelectorAll('input').forEach(input => {
        input.addEventListener('input', function() {
            this.classList.remove('border-accent-400');
            const errorEl = document.getElementById(this.id + '_error');
            if (errorEl) {
                errorEl.classList.add('hidden');
            }
        });
    });
    
    // Phone number: only allow numbers and limit to 11 digits
    const phoneInput = document.getElementById('phone');
    if (phoneInput) {
        phoneInput.addEventListener('input', function(e) {
            // Remove any non-numeric characters
            this.value = this.value.replace(/[^0-9]/g, '');
            
            // Limit to 11 digits
            if (this.value.length > 11) {
                this.value = this.value.slice(0, 11);
            }
        });
        
        // Prevent paste of non-numeric content
        phoneInput.addEventListener('paste', function(e) {
            e.preventDefault();
            const pastedText = (e.clipboardData || window.clipboardData).getData('text');
            const numericOnly = pastedText.replace(/[^0-9]/g, '').slice(0, 11);
            this.value = numericOnly;
        });
    }

    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        // Validate form
        if (!validateForm()) {
            Swal.fire({
                icon: 'error',
                title: 'Validation Error',
                text: 'Please fix the errors in the form',
                background: '#0f172a',
                color: '#e2e8f0',
                iconColor: '#FF6B6B',
                confirmButtonColor: '#4ECDC4'
            });
            return;
        }
        
        // Show confirmation dialog
        Swal.fire({
            title: 'Create Rescuer Account?',
            html: `
                <div class="text-left space-y-2">
                    <p class="text-sm"><strong class="text-secondary-400">Name:</strong> ${form.full_name.value}</p>
                    <p class="text-sm"><strong class="text-secondary-400">Email:</strong> ${form.email.value}</p>
                    <p class="text-sm"><strong class="text-secondary-400">Phone:</strong> ${form.phone.value || 'Not provided'}</p>
                    <p class="mt-4 text-xs text-primary-400">The user will be able to login with this email and password in the mobile app.</p>
                </div>
            `,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#4ECDC4',
            cancelButtonColor: '#334155',
            confirmButtonText: 'Yes, create account',
            cancelButtonText: 'Cancel',
            background: '#0f172a',
            color: '#e2e8f0',
            iconColor: '#4ECDC4'
        }).then((result) => {
            if (result.isConfirmed) {
                // Show loading state
                submitBtn.disabled = true;
                btnText.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Creating...';
                
                // Show loading alert
                Swal.fire({
                    title: 'Creating Account...',
                    html: 'Please wait while we create the rescuer account.',
                    allowOutsideClick: false,
                    allowEscapeKey: false,
                    background: '#0f172a',
                    color: '#e2e8f0',
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });
                
                // Submit form
                form.submit();
            }
        });
    });
</script>
@endpush
