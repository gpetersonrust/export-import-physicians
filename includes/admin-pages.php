<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class ExportImportHealthcareProviders {
    public function __construct() {
        // Hook into admin menu creation
        add_action( 'admin_menu', [ $this, 'register_admin_pages' ] );
    }

    // Register admin menu
    public function register_admin_pages() {
        add_menu_page(
            'Export Import Healthcare Providers',
            'Healthcare Providers Import/Export',
            'manage_options',
            'eip-healthcare-providers',
            [ $this, 'download_providers_page' ],
            'dashicons-admin-users'
        );

        add_submenu_page(
            'eip-healthcare-providers',
            'Download Providers',
            'Download',
            'manage_options',
            'eip-download-providers',
            [ $this, 'download_providers_page' ]
        );

        add_submenu_page(
            'eip-healthcare-providers',
            'Import Providers',
            'Import',
            'manage_options',
            'eip-import-providers',
            [ $this, 'import_providers_page' ]
        );
    }

    // Callback for download page
    public function download_providers_page() {
        echo '<div class="wrap">';
        echo '<h1>Download Healthcare Providers</h1>';
        echo '<form method="post" action="">';
        echo '<input type="hidden" name="eip_download_providers" value="1">';
        submit_button('Download');
        echo '</form>';
        echo '</div>';
    
        if (isset($_POST['eip_download_providers']) && $_POST['eip_download_providers'] == '1') {
            // Retrieve providers posts (add your post types here)
            $post_types = ['physician', 'smoatc', 'physical_therapists', 'ap_providers'];
            $providers_data = [];

            foreach ($post_types as $post_type) {
                $providers_posts = get_posts([
                    'post_type' => $post_type,
                    'posts_per_page' => -1
                ]);

                foreach ($providers_posts as $provider) {
                    // Add thumbnail URL
                    $thumbnail_id = get_post_thumbnail_id($provider->ID);
                    $thumbnail_url = wp_get_attachment_image_src($thumbnail_id, 'full', true);
                    $provider->thumbnail_url = $thumbnail_url[0];
                    $provider->meta_data = [];

                    // Loop through the custom fields and add them to the provider data
                    foreach ($this->get_custom_fields($post_type) as $field) {
                        $provider->meta_data[$field] = get_post_meta($provider->ID, $field, true);
                    }

                    // Add to array
                    $providers_data[] = $provider;
                }
            }
    
            // Create the JSON file
            $providers_json = json_encode( $providers_data, JSON_PRETTY_PRINT );
    
            // Define the file path
            $file_path = plugin_dir_path( __FILE__ ) . 'providers.json';
    
            // Save the JSON data to a file
            file_put_contents($file_path, $providers_json);
    
            // Set headers for the file download
            header('Content-Type: application/json');
            header('Content-Disposition: attachment; filename="providers.json"');
    
            // Ensure no output has been sent before the download begins
            ob_clean();
            flush();
    
            // Output the file contents to the browser
            readfile($file_path);
    
            // Exit to stop further execution and prevent page rendering
            exit;
        }
    }
    
    // Callback for import page
    public function import_providers_page() {
        echo '<div class="wrap">';
        echo '<h1>Import Healthcare Providers</h1>';
        echo '<form method="post" enctype="multipart/form-data" action="">';
        echo '<input type="file" name="eip_import_file" accept=".json">';
        submit_button('Import');
        echo '</form>';
        echo '</div>';

        if (isset($_FILES['eip_import_file']) && $_FILES['eip_import_file']['error'] == UPLOAD_ERR_OK) {
            $file = $_FILES['eip_import_file']['tmp_name'];
            $json_data = file_get_contents($file);
            $providers = json_decode($json_data, true);

            if ($providers !== null) {
                // Iterate through providers and create or update
                foreach ($providers as $provider_data) {
                    $post_name = sanitize_title($provider_data['post_name']);
                    $post_type = $provider_data['post_type'];
                    $existing_post = get_page_by_path($post_name, OBJECT, $post_type);

                    if ($existing_post) {
                        // Update the existing post
                        $this->update_provider_post($existing_post->ID, $provider_data);
                    } else {
                        // Create a new post
                        $this->create_provider_post($provider_data);
                    }
                }
            } else {
                echo '<div class="error"><p>Invalid JSON file.</p></div>';
            }
        }
    }

    // Helper function for custom fields (different for each post type)
    private function get_custom_fields($post_type) {
        $fields = [
            'personal_information',
            'last_name',
            'title',
            'social_climb_id',
            'specialty',
            'secondary_specialty',
            'education',
            'intership',
            'residency',
            'felloswhip',
            'began_practice_at_koc',
            'board_certification',
            'professional_distinctions',
            'orthopaedic_specialty',
            'office_info',
            'office_location',
            'appointments_number',
            'administrative_assistant',
            'nurse',
            'professional_interests',
            'teaching_appointments',
            'medical_associations',
            'educational_links',
            'patient_forms',
            'procedures_performed',
            'conditions_treated',
            'schedule_an_appointment_link',
            'appointment_button_text',
            'affiliation',
            'affiliation_link',
            'Surgery',
            'api_name',
            'professional_headshot'
        ];

        return $fields;
    }

    // Utility method to download and save an image
    private function download_image($url, $post_id) {
        // Check if the URL is a local domain
        $is_local = strpos($url, '.local') !== false;
        
        // Set SSL context options
        $context_options = [
            'http' => [
                'method' => 'GET',
                'header' => 'Accept: application/json',
            ]
        ];
    
        if ($is_local) {
            // Disable SSL verification for local domain
            $context_options['ssl'] = [
                'verify_peer' => false,
                'verify_peer_name' => false,
            ];
        }
    
        $context = stream_context_create($context_options);
        
        // Fetch image content with context
        $image_data = file_get_contents($url, false, $context);
        if ($image_data === false) {
            // Handle the error
            return false;
        }
    
        $image_name = basename($url);
        $upload_dir = wp_upload_dir();
        $image_path = $upload_dir['path'] . '/' . $image_name;
        
        file_put_contents($image_path, $image_data);
        
        $wp_filetype = wp_check_filetype($image_name, null);
        $attachment = array(
            'post_mime_type' => $wp_filetype['type'],
            'post_title'     => sanitize_file_name($image_name),
            'post_content'   => '',
            'post_status'    => 'inherit'
        );
        
        $attach_id = wp_insert_attachment($attachment, $image_path, $post_id);
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        $attach_data = wp_generate_attachment_metadata($attach_id, $image_path);
        wp_update_attachment_metadata($attach_id, $attach_data);
        
        return $attach_id;
    }

    // Method to update an existing provider post
    private function update_provider_post($post_id, $provider_data) {
        // Update post fields
        foreach ($provider_data['meta_data'] as $key => $value) {
            update_post_meta($post_id, $key, $value);
        }

        // Handle thumbnail
        if (empty(get_post_thumbnail_id($post_id)) && !empty($provider_data['thumbnail_url'])) {
            $this->download_image($provider_data['thumbnail_url'], $post_id);
        }
    }

    // Method to create a new provider post
    private function create_provider_post($provider_data) {
        $post_data = [
            'post_title'   => $provider_data['post_title'],
            'post_type'    => $provider_data['post_type'],
            'post_status'  => 'publish',
            'post_name'    => sanitize_title($provider_data['post_name']),
        ];

        $post_id = wp_insert_post($post_data);

        // Save custom fields
        foreach ($provider_data['meta_data'] as $key => $value) {
            update_post_meta($post_id, $key, $value);
        }

        // Handle thumbnail
        if (!empty($provider_data['thumbnail_url'])) {
            $attach_id = $this->download_image($provider_data['thumbnail_url'], $post_id);
            set_post_thumbnail($post_id, $attach_id);
        }
    }
}

// Initialize the plugin
new ExportImportHealthcareProviders();
?>