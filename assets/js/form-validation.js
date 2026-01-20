/**
 * Form Validation Utilities with Vue.js 3 Support
 * IE11 Compatible
 */

// Vue form validation mixin with IE11 compatibility
const formValidationMixin = {
  data: function() {
    return {
      errors: {},
      isSubmitting: false,
      touched: {}
    };
  },
  methods: {
    /**
     * Validate a single field
     * @param {string} field - Field name
     * @param {object} rules - Validation rules
     * @param {*} value - Optional value override
     * @returns {boolean} - True if valid
     */
    validateField: function(field, rules, value) {
      var fieldValue = value !== undefined ? value : this[field];
      var errors = [];

      // Required validation
      if (rules.required && (!fieldValue || fieldValue.toString().trim() === '')) {
        errors.push(rules.requiredMessage || 'This field is required');
      }

      // Skip other validations if empty and not required
      if (!fieldValue && !rules.required) {
        if (this.errors[field]) {
          delete this.errors[field];
        }
        return true;
      }

      // Min length validation
      if (rules.minLength && fieldValue.toString().length < rules.minLength) {
        errors.push('Minimum length is ' + rules.minLength + ' characters');
      }

      // Max length validation
      if (rules.maxLength && fieldValue.toString().length > rules.maxLength) {
        errors.push('Maximum length is ' + rules.maxLength + ' characters');
      }

      // Email validation
      if (rules.email && fieldValue) {
        var emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(fieldValue)) {
          errors.push('Invalid email format');
        }
      }

      // Numeric validation
      if (rules.numeric && fieldValue && isNaN(fieldValue)) {
        errors.push('Must be a number');
      }

      // Min value validation
      if (rules.min !== undefined && parseFloat(fieldValue) < rules.min) {
        errors.push('Minimum value is ' + rules.min);
      }

      // Max value validation
      if (rules.max !== undefined && parseFloat(fieldValue) > rules.max) {
        errors.push('Maximum value is ' + rules.max);
      }

      // Pattern validation
      if (rules.pattern && fieldValue) {
        var regex = new RegExp(rules.pattern);
        if (!regex.test(fieldValue)) {
          errors.push(rules.patternMessage || 'Invalid format');
        }
      }

      // Custom validation function
      if (rules.custom && typeof rules.custom === 'function') {
        var customResult = rules.custom(fieldValue);
        if (customResult !== true) {
          errors.push(customResult || 'Invalid value');
        }
      }

      // Update errors object
      if (errors.length > 0) {
        this.errors[field] = errors[0]; // Show first error only
      } else {
        if (this.errors[field]) {
          delete this.errors[field];
        }
      }

      return errors.length === 0;
    },

    /**
     * Validate entire form
     * @param {object} fields - Object with field names and rules
     * @returns {boolean} - True if all fields are valid
     */
    validateForm: function(fields) {
      var isValid = true;
      var self = this;
      var fieldNames = Object.keys(fields);

      for (var i = 0; i < fieldNames.length; i++) {
        var field = fieldNames[i];
        if (!self.validateField(field, fields[field])) {
          isValid = false;
        }
      }

      return isValid;
    },

    /**
     * Mark field as touched (for showing errors after user interaction)
     * @param {string} field - Field name
     */
    touchField: function(field) {
      this.touched[field] = true;
    },

    /**
     * Check if field has been touched
     * @param {string} field - Field name
     * @returns {boolean}
     */
    isTouched: function(field) {
      return !!this.touched[field];
    },

    /**
     * Check if field should show error
     * @param {string} field - Field name
     * @returns {boolean}
     */
    shouldShowError: function(field) {
      return this.isTouched(field) && !!this.errors[field];
    },

    /**
     * Clear all errors
     */
    clearErrors: function() {
      this.errors = {};
      this.touched = {};
    },

    /**
     * Clear error for specific field
     * @param {string} field - Field name
     */
    clearFieldError: function(field) {
      if (this.errors[field]) {
        delete this.errors[field];
      }
      if (this.touched[field]) {
        delete this.touched[field];
      }
    }
  }
};

// Export for use in other scripts
if (typeof module !== 'undefined' && module.exports) {
  module.exports = { formValidationMixin };
}

/**
 * jQuery-based form validation (for non-Vue forms)
 */
(function($) {
  'use strict';

  // Add custom validation methods to jQuery Validator (if available)
  if ($.validator) {
    // Phone number validation
    $.validator.addMethod('phone', function(value, element) {
      return this.optional(element) || /^[\d\s\-\+\(\)]+$/.test(value);
    }, 'Please enter a valid phone number');

    // Currency validation (Zambian Kwacha)
    $.validator.addMethod('currency', function(value, element) {
      return this.optional(element) || /^\d+(\.\d{1,2})?$/.test(value);
    }, 'Please enter a valid amount');

    // Date validation (YYYY-MM-DD)
    $.validator.addMethod('dateISO8601', function(value, element) {
      return this.optional(element) || /^\d{4}-\d{2}-\d{2}$/.test(value);
    }, 'Please enter a valid date (YYYY-MM-DD)');
  }

  // Form validation helper class
  window.FormValidator = function(formSelector, options) {
    this.$form = $(formSelector);
    this.options = $.extend({
      errorClass: 'is-invalid',
      validClass: 'is-valid',
      errorElement: 'div',
      errorPlacement: function(error, element) {
        error.addClass('invalid-feedback');
        element.closest('.form-group').append(error);
      },
      highlight: function(element) {
        $(element).addClass('is-invalid').removeClass('is-valid');
      },
      unhighlight: function(element) {
        $(element).removeClass('is-invalid').addClass('is-valid');
      },
      submitHandler: null
    }, options);

    this.init();
  };

  FormValidator.prototype = {
    init: function() {
      var self = this;

      if (this.$form.length === 0) {
        console.warn('Form not found:', this.$form.selector);
        return;
      }

      // Add Bootstrap validation classes
      this.$form.on('submit', function(e) {
        if (!self.validate()) {
          e.preventDefault();
          e.stopPropagation();
          return false;
        }

        if (self.options.submitHandler) {
          e.preventDefault();
          self.options.submitHandler(self.$form);
        }
      });

      // Real-time validation
      this.$form.find('input, select, textarea').on('blur', function() {
        self.validateField($(this));
      });
    },

    validate: function() {
      var isValid = true;
      var self = this;

      this.$form.find('[required], [data-validate]').each(function() {
        if (!self.validateField($(this))) {
          isValid = false;
        }
      });

      return isValid;
    },

    validateField: function($field) {
      var value = $field.val();
      var rules = $field.data('validate') || {};
      var isValid = true;
      var errorMsg = '';

      // Required validation
      if ($field.prop('required') && !value) {
        isValid = false;
        errorMsg = 'This field is required';
      }

      // Email validation
      if (isValid && $field.attr('type') === 'email') {
        var emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (value && !emailRegex.test(value)) {
          isValid = false;
          errorMsg = 'Invalid email format';
        }
      }

      // Number validation
      if (isValid && $field.attr('type') === 'number') {
        if (value && isNaN(value)) {
          isValid = false;
          errorMsg = 'Must be a number';
        }

        // Min/Max
        var min = $field.attr('min');
        var max = $field.attr('max');
        if (min && parseFloat(value) < parseFloat(min)) {
          isValid = false;
          errorMsg = 'Minimum value is ' + min;
        }
        if (max && parseFloat(value) > parseFloat(max)) {
          isValid = false;
          errorMsg = 'Maximum value is ' + max;
        }
      }

      // Update UI
      if (isValid) {
        this.options.unhighlight($field[0]);
        $field.siblings('.invalid-feedback').remove();
      } else {
        this.options.highlight($field[0]);
        var $error = $field.siblings('.invalid-feedback');
        if ($error.length === 0) {
          $error = $('<div class="invalid-feedback"></div>');
          $field.after($error);
        }
        $error.text(errorMsg);
      }

      return isValid;
    },

    clearErrors: function() {
      this.$form.find('.is-invalid').removeClass('is-invalid');
      this.$form.find('.is-valid').removeClass('is-valid');
      this.$form.find('.invalid-feedback').remove();
    }
  };

})(jQuery);
