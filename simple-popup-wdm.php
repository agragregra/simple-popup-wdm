<?php
/**
 * Plugin Name: Simple Popup WDM
 * Description: Simple Popup WordPress Plugin
 * Version: 1.0
 * Author: WebDesign Master
 * Text Domain: simple-popup-wdm
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

defined('ABSPATH') || exit;

class Simple_Popup_WDM {

    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_assets'));
        add_action('wp_footer', array($this, 'add_popup_html'));
    }

    public function add_admin_menu() {
        add_options_page(
            __('Simple Popup', 'simple-popup-wdm'),
            __('Simple Popup', 'simple-popup-wdm'),
            'manage_options',
            'simple-popup',
            array($this, 'admin_page')
        );
    }

    public function register_settings() {
        register_setting('simple_popup_settings', 'simple_popup_data', array(
            'type' => 'array',
            'sanitize_callback' => array($this, 'sanitize_popup_data'),
            'default' => array(
                array(
                    'trigger_class' => 'open-popup',
                    'title' => __('Popup Title', 'simple-popup-wdm'),
                    'content' => __('Popup content goes here...', 'simple-popup-wdm')
                )
            )
        ));
    }

    public function sanitize_popup_data($input) {
        if (!isset($_POST['simple_popup_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['simple_popup_nonce'])), 'simple_popup_settings_nonce')) {
            return get_option('simple_popup_data', array());
        }

        if (!current_user_can('manage_options')) {
            return get_option('simple_popup_data', array());
        }

        if (!is_array($input)) {
            return array();
        }

        $output = array();
        $used_classes = array();

        foreach ($input as $popup) {
            if (!empty($popup['trigger_class']) && !empty($popup['content'])) {
                $trigger_class = sanitize_text_field($popup['trigger_class']);

                if (in_array($trigger_class, $used_classes)) {
                    continue;
                }

                $used_classes[] = $trigger_class;
                $output[] = array(
                    'trigger_class' => $trigger_class,
                    'title' => sanitize_text_field($popup['title']),
                    'content' => wp_kses_post($popup['content'])
                );
            }
        }

        return empty($output) ? array(
            array(
                'trigger_class' => 'open-popup',
                'title' => __('Popup Title', 'simple-popup-wdm'),
                'content' => __('Popup content goes here...', 'simple-popup-wdm')
            )
        ) : $output;
    }

    public function admin_page() {
        if (!current_user_can('manage_options')) {
            return;
        }

        if (isset($_POST['simple_popup_nonce']) && !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['simple_popup_nonce'])), 'simple_popup_settings_nonce')) {
          wp_die('Security check failed');
        }

        $popup_data = get_option('simple_popup_data', array());
        ?>

      <style>
        .form-field {
          margin-bottom: 10px;
        }
        .form-field label {
          display: block;
          margin-bottom: 3px;
        }
        hr {
          margin: 15px 0;
        }
        p.submit {
          margin-top: 0;
          padding-top: 0;
        }
      </style>

        <div class="wrap">
            <h1><?php esc_html_e('Simple Popup', 'simple-popup-wdm'); ?></h1>
            <hr>
            <?php if (isset($_GET['settings-updated'])): ?>
                <div id="message" class="updated notice is-dismissible">
                    <p><?php esc_html_e('Settings saved successfully.', 'simple-popup-wdm'); ?></p>
                </div>
            <?php endif; ?>

            <form method="post" action="options.php" id="simple-popup-form">
                <?php settings_fields('simple_popup_settings'); ?>
                <?php wp_nonce_field('simple_popup_settings_nonce', 'simple_popup_nonce'); ?>

                <div id="simple-popup-container">
                    <?php foreach ($popup_data as $index => $popup): ?>
                    <div class="simple-popup-item">
                    <h3><?php
                      /* translators: %d: Popup number */
                      printf(esc_html__('Popup #%d', 'simple-popup-wdm'), (int) $index + 1);
                      ?></h3>

                        <div class="form-field">
                            <label for="simple_popup_trigger_class_<?php echo esc_attr($index); ?>">
                                <?php esc_html_e('CSS Class (Trigger)', 'simple-popup-wdm'); ?>
                            </label>
                            <input type="text" 
                                   id="simple_popup_trigger_class_<?php echo esc_attr($index); ?>"
                                   name="simple_popup_data[<?php echo esc_attr($index); ?>][trigger_class]" 
                                   value="<?php echo esc_attr($popup['trigger_class']); ?>" 
                                   class="regular-text trigger-class-input"
                                   required>
                        </div>

                        <div class="form-field">
                            <label for="simple_popup_title_<?php echo esc_attr($index); ?>">
                                <?php esc_html_e('Popup Title', 'simple-popup-wdm'); ?>
                            </label>
                            <input type="text" 
                                   id="simple_popup_title_<?php echo esc_attr($index); ?>"
                                   name="simple_popup_data[<?php echo esc_attr($index); ?>][title]" 
                                   value="<?php echo esc_attr($popup['title']); ?>" 
                                   class="regular-text">
                        </div>

                        <div class="form-field">
                            <label for="simple_popup_content_<?php echo esc_attr($index); ?>">
                                <?php esc_html_e('Content', 'simple-popup-wdm'); ?>
                            </label>
                            <textarea id="simple_popup_content_<?php echo esc_attr($index); ?>"
                                      name="simple_popup_data[<?php echo esc_attr($index); ?>][content]" 
                                      class="large-text" 
                                      rows="5" 
                                      required><?php echo esc_textarea($popup['content']); ?></textarea>
                            <p class="description">
                                <?php esc_html_e('HTML, shortcodes, or plain text', 'simple-popup-wdm'); ?>
                            </p>
                        </div>

                        <button type="button" class="button button-secondary simple-remove-popup">
                            <?php esc_html_e('Remove Popup', 'simple-popup-wdm'); ?>
                        </button>
                        <hr>
                    </div>
                    <?php endforeach; ?>
                </div>

                <button type="button" class="button" id="simple-add-popup">
                    <?php esc_html_e('+ Add New Popup', 'simple-popup-wdm'); ?>
                </button>

                <hr>

                <?php submit_button(__('Save Changes', 'simple-popup-wdm')); ?>
            </form>

            <script>
            document.addEventListener('DOMContentLoaded', function() {
                const container = document.getElementById('simple-popup-container');
                const addButton = document.getElementById('simple-add-popup');
                const form = document.getElementById('simple-popup-form');

                if (!container || !addButton) return;

                function validateUniqueClasses() {
                    const classInputs = container.querySelectorAll('input[name$="[trigger_class]"]');
                    const classes = new Set();
                    let hasDuplicates = false;

                    classInputs.forEach(input => {
                        const value = input.value.trim();
                        if (value) {
                            if (classes.has(value)) {
                                hasDuplicates = true;
                                input.style.borderColor = '#dc3232';
                            } else {
                                classes.add(value);
                                input.style.borderColor = '';
                            }
                        }
                    });

                    return !hasDuplicates;
                }

                function showError(message) {
                    const existingNotice = document.querySelector('.simple-popup-notice');
                    if (existingNotice) {
                        existingNotice.remove();
                    }

                    const notice = document.createElement('div');
                    notice.className = 'notice notice-error simple-popup-notice is-dismissible';
                    notice.innerHTML = `<p>${message}</p>`;

                    const h1 = document.querySelector('.wrap h1');
                    h1.parentNode.insertBefore(notice, h1.nextSibling);
                }

                addButton.addEventListener('click', function() {
                    const index = container.children.length;
                    const newItem = document.createElement('div');
                    newItem.className = 'simple-popup-item';
                    newItem.innerHTML = `
                        <h3>Popup #${index + 1}</h3>

                        <div class="form-field">
                            <label>CSS Class (Trigger)</label>
                            <input type="text" 
                                   name="simple_popup_data[${index}][trigger_class]" 
                                   class="regular-text trigger-class-input" 
                                   value="open-popup-${index + 1}" 
                                   required>
                        </div>

                        <div class="form-field">
                            <label>Popup Title</label>
                            <input type="text" 
                                   name="simple_popup_data[${index}][title]" 
                                   class="regular-text" 
                                   value="Popup Title ${index + 1}">
                        </div>

                        <div class="form-field">
                            <label>Content</label>
                            <textarea name="simple_popup_data[${index}][content]" 
                                      class="large-text" 
                                      rows="5" 
                                      required>Popup content ${index + 1}</textarea>
                            <p class="description">HTML, shortcodes, or plain text</p>
                        </div>

                        <button type="button" class="button button-secondary simple-remove-popup">Remove Popup</button>
                        <hr>
                    `;
                    container.appendChild(newItem);

                    const newInput = newItem.querySelector('.trigger-class-input');
                    newInput.addEventListener('input', validateUniqueClasses);
                });

                container.addEventListener('click', function(e) {
                    if (e.target.classList.contains('simple-remove-popup')) {
                        e.target.closest('.simple-popup-item').remove();
                        validateUniqueClasses();
                    }
                });

                if (form) {
                    form.addEventListener('submit', function(e) {
                        if (!validateUniqueClasses()) {
                            e.preventDefault();
                            showError('Error: CSS classes must be unique. Please fix duplicate classes before saving.');
                            return false;
                        }
                    });
                }

                const existingInputs = container.querySelectorAll('.trigger-class-input');
                existingInputs.forEach(input => {
                    input.addEventListener('input', validateUniqueClasses);
                });
            });
            </script>

        </div>
        <?php
    }

    public function enqueue_assets() {
        if (is_admin()) {
            return;
        }

        wp_enqueue_style('simple-popup-style', plugin_dir_url(__FILE__) . 'simple-popup-wdm.css', array(), '1.0');
        wp_enqueue_script('simple-popup-script', plugin_dir_url(__FILE__) . 'simple-popup-wdm.js', array(), '1.0', true);

        $popup_data = get_option('simple_popup_data', array());
        wp_localize_script('simple-popup-script', 'simplePopupSettings', array(
            'popups' => $popup_data
        ));
    }

    public function add_popup_html() {
        if (is_admin()) {
            return;
        }

        $popup_data = get_option('simple_popup_data', array());

        foreach ($popup_data as $index => $popup) {
            if (empty($popup['trigger_class']) || empty($popup['content'])) {
                continue;
            }
            ?>
            <div id="simple-popup-<?php echo esc_attr($index); ?>" class="simple-popup" aria-hidden="true">
                <div class="simple-popup__overlay" data-popup-id="<?php echo esc_attr($index); ?>"></div>
                <div class="simple-popup__content">
                    <button class="simple-popup__close" data-popup-id="<?php echo esc_attr($index); ?>">&times;</button>
                    <?php if (!empty($popup['title'])): ?>
                    <h3 class="simple-popup__title"><?php echo esc_html($popup['title']); ?></h3>
                    <?php endif; ?>
                    <div class="simple-popup__body">
                        <?php echo do_shortcode(wp_kses_post($popup['content'])); ?>
                    </div>
                </div>
            </div>
            <?php
        }
    }
}

function simple_popup_init() {
    new Simple_Popup_WDM();
}
add_action('plugins_loaded', 'simple_popup_init');
