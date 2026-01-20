<!-- Modern Slide-Over Component (Vue.js 3) -->
<div id="slideOverApp">
  <div class="slide-over-overlay"
       :class="{ active: isOpen }"
       @click="close"></div>

  <div class="slide-over" :class="{ active: isOpen }">
    <div class="slide-over-header">
      <h3>{{ title }}</h3>
      <button class="slide-over-close" @click="close" type="button">
        <i class="fa fa-times"></i>
      </button>
    </div>

    <div class="slide-over-body" v-html="content"></div>

    <div class="slide-over-footer" v-if="showFooter">
      <button type="button" class="btn btn-primary" @click="submitForm">
        <i class="fa fa-save"></i> Save
      </button>
      <button type="button" class="btn btn-secondary" @click="close">
        <i class="fa fa-times"></i> Cancel
      </button>
    </div>
  </div>
</div>

<style>
/* Slide-Over Component Styles */
.slide-over-overlay {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background: rgba(0, 0, 0, 0.5);
  opacity: 0;
  visibility: hidden;
  transition: all 0.3s ease;
  z-index: 1040;
}

.slide-over-overlay.active {
  opacity: 1;
  visibility: visible;
}

.slide-over {
  position: fixed;
  top: 0;
  right: -600px;
  width: 600px;
  max-width: 90%;
  height: 100vh;
  background: white;
  box-shadow: -4px 0 20px rgba(0,0,0,0.15);
  z-index: 1050;
  transition: right 0.3s ease;
  display: -ms-flexbox; /* IE11 */
  display: flex;
  -ms-flex-direction: column; /* IE11 */
  flex-direction: column;
}

.slide-over.active {
  right: 0;
}

.slide-over-header {
  padding: 1.5rem;
  border-bottom: 1px solid #e5e7eb;
  border-bottom: 1px solid var(--gray-200, #e5e7eb);
  display: -ms-flexbox; /* IE11 */
  display: flex;
  -ms-flex-pack: justify; /* IE11 */
  justify-content: space-between;
  -ms-flex-align: center; /* IE11 */
  align-items: center;
  background: #f9fafb;
  background: var(--gray-50, #f9fafb);
  -ms-flex-negative: 0; /* IE11 */
  flex-shrink: 0;
}

.slide-over-header h3 {
  margin: 0;
  font-size: 1.25rem;
  font-weight: 600;
  color: #1f2937;
  color: var(--gray-800, #1f2937);
}

.slide-over-close {
  background: none;
  border: none;
  font-size: 1.5rem;
  color: #6b7280;
  color: var(--gray-500, #6b7280);
  cursor: pointer;
  padding: 0.25rem;
  line-height: 1;
  width: 32px;
  height: 32px;
  border-radius: 0.375rem;
  transition: all 0.2s;
  display: -ms-flexbox; /* IE11 */
  display: flex;
  -ms-flex-align: center; /* IE11 */
  align-items: center;
  -ms-flex-pack: center; /* IE11 */
  justify-content: center;
}

.slide-over-close:hover {
  background: #f3f4f6;
  background: var(--gray-100, #f3f4f6);
  color: #1f2937;
  color: var(--gray-800, #1f2937);
}

.slide-over-body {
  -ms-flex: 1; /* IE11 */
  flex: 1;
  overflow-y: auto;
  padding: 1.5rem;
}

.slide-over-footer {
  padding: 1rem 1.5rem;
  border-top: 1px solid #e5e7eb;
  border-top: 1px solid var(--gray-200, #e5e7eb);
  background: #f9fafb;
  background: var(--gray-50, #f9fafb);
  display: -ms-flexbox; /* IE11 */
  display: flex;
  -ms-flex-pack: end; /* IE11 */
  justify-content: flex-end;
  gap: 0.5rem;
  -ms-flex-negative: 0; /* IE11 */
  flex-shrink: 0;
}

/* Large slide-over variant */
.slide-over.slide-over-large {
  width: 800px;
}

/* Extra large slide-over variant */
.slide-over.slide-over-xl {
  width: 1000px;
}

/* Mobile responsive */
@media (max-width: 768px) {
  .slide-over {
    width: 100%;
    max-width: 100%;
  }
}
</style>

<script>
// Vue.js 3 Slide-Over Component
(function() {
  const { createApp } = Vue;

  const slideOverApp = createApp({
    data: function() {
      return {
        isOpen: false,
        title: '',
        content: '',
        showFooter: true,
        size: 'default' // default, large, xl
      };
    },
    methods: {
      open: function(title, url, options) {
        var self = this;
        self.title = title;
        self.isOpen = true;

        // Handle options
        if (options) {
          self.showFooter = options.showFooter !== false;
          self.size = options.size || 'default';
        }

        // Show loading state
        self.content = '<div class="text-center py-5"><div class="spinner-border text-primary" role="status"><span class="sr-only">Loading...</span></div><p class="mt-3 text-muted">Loading form...</p></div>';

        // Fetch content via AJAX
        $.ajax({
          url: url,
          method: 'GET',
          success: function(response) {
            self.content = response;

            // Re-initialize form plugins after content load
            setTimeout(function() {
              // Initialize Select2
              $('.slide-over-body .select2').select2({
                placeholder: "Please select here",
                width: "100%",
                dropdownParent: $('.slide-over-body')
              });

              // Initialize DateTimePicker
              $('.slide-over-body .datetimepicker').datetimepicker({
                format: 'Y/m/d H:i',
                startDate: '+3d'
              });

              // Initialize Date Picker
              $('.slide-over-body .datepicker').datepicker({
                format: 'yyyy-mm-dd',
                autoclose: true
              });
            }, 100);
          },
          error: function(err) {
            console.error('Slide-over load error:', err);
            self.content = '<div class="alert alert-danger m-3"><i class="fa fa-exclamation-circle"></i> Failed to load form. Please try again.</div>';
            setTimeout(function() {
              self.close();
            }, 3000);
          }
        });
      },
      close: function() {
        var self = this;
        self.isOpen = false;
        setTimeout(function() {
          self.content = '';
          self.title = '';
          self.showFooter = true;
          self.size = 'default';
        }, 300);
      },
      submitForm: function() {
        // Find and submit the form inside slide-over
        var form = $('.slide-over-body form');
        if (form.length > 0) {
          form.submit();
        }
      }
    }
  });

  // Wait for DOM to be ready before mounting
  $(document).ready(function() {
    window.slideOverInstance = slideOverApp.mount('#slideOverApp');
  });

  // Global function to open slide-over (replaces uni_modal)
  window.slide_over = function(title, url, size) {
    if (window.slideOverInstance) {
      var options = {
        showFooter: true,
        size: size || 'default'
      };
      window.slideOverInstance.open(title, url, options);
    } else {
      console.error('Slide-over component not initialized');
    }
  };
})();
</script>
