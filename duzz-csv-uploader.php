<?php

/**
 * Plugin Name: Duzz CSV Uploader Add-On Plugin
 * Plugin URI: https://duzz.io/
 * Description: Duzz CSV Uploader add-on plugin for the Duzz Custom Portal
 * Version: 1.0.1
 * Author: Streater Kelley
 * Author URI: https://duzz.io/about-us/
 * Text Domain: duzz-csv-uploader
 * Domain Path: /languages
 * License: GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Include the PHPSpreadsheet library.
require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

class Excel_Spreadsheet_Uploader {

    public function __construct() {
        add_action( 'admin_menu', array( $this, 'register_menu' ) );
        add_action( 'admin_post_upload_excel', array( $this, 'handle_upload' ) );
    }

    public function register_menu() {
        add_menu_page(
            'Excel Uploader',
            'Excel Uploader',
            'manage_options',
            'excel-uploader',
            array( $this, 'upload_form' ),
            'dashicons-upload'
        );
    }

    public function upload_form() {
        ?>
        <div class="wrap">
            <h1><?php _e( 'Upload CSV File', 'excel-uploader' ); ?></h1>
            <form method="post" enctype="multipart/form-data" action="<?php echo admin_url( 'admin-post.php' ); ?>">
                <input type="hidden" name="action" value="upload_excel">
                <input type="file" name="excel_file" accept=".xls,.xlsx,.csv">
                <?php submit_button( 'Upload' ); ?>
            </form>
        </div>
        <?php
    }

    public function handle_upload() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        if ( ! isset( $_FILES['excel_file'] ) ) {
            wp_redirect( admin_url( 'admin.php?page=excel-uploader&error=1' ) );
            exit;
        }

        $file = $_FILES['excel_file'];
        $file_path = $file['tmp_name'];
        $file_ext = pathinfo($file['name'], PATHINFO_EXTENSION);

        if ($file_ext === 'csv') {
            $reader = IOFactory::createReader('Csv');
        } else {
            $reader = IOFactory::createReader('Xlsx');
        }

        try {
            $spreadsheet = $reader->load($file_path);
            $sheet = $spreadsheet->getActiveSheet();
            $data = $sheet->toArray();

            foreach ( $data as $row_index => $row ) {
                if ( $row_index == 0 ) {
                    continue; // Skip the header row
                }

                $day = $row[0];
                $mic = $row[1];
                $frequency = $row[2];
                $time = $row[3];
                $region = $row[4];
                $free = $row[5];
                $venue = $row[6];
                $details = $row[7];
                $instagram = $row[8];
                $website = $row[9];
                $address = $row[10];

                // Check if the mic_venue post already exists
                $existing_venue = get_posts(array(
                    'post_type' => 'mic_venue',
                    'title' => wp_strip_all_tags( $venue ),
                    'post_status' => 'publish',
                    'posts_per_page' => 1,
                ));

                if ($existing_venue) {
                    // Use the existing venue ID
                    $mic_venue_id = $existing_venue[0]->ID;
                } else {
                    // Create a new mic_venue post
                    $mic_venue_post = array(
                        'post_title'    => wp_strip_all_tags( $venue ),
                        'post_type'     => 'mic_venue',
                        'post_status'   => 'publish',
                    );
                    $mic_venue_id = wp_insert_post( $mic_venue_post );

                    // Add address to mic_venue
                    update_post_meta( $mic_venue_id, 'address', $address );
                }

                // Create the comedy_event post
                $comedy_event_post = array(
                    'post_title'    => wp_strip_all_tags( $mic ),
                    'post_type'     => 'comedy_event',
                    'post_status'   => 'publish',
                );
                $comedy_event_id = wp_insert_post( $comedy_event_post );

                // Format the time and day for the 'time' field
                $time_field_value = $time . ' : ' . $day;

                // Add custom fields to comedy_event
                update_field( 'time', $time_field_value, $comedy_event_id );
                update_field( 'frequency', $frequency, $comedy_event_id );
                update_field( 'region', $region, $comedy_event_id );
                update_field( 'free', $free, $comedy_event_id );
                update_field( 'venue', $mic_venue_id, $comedy_event_id );
                update_field( 'details', $details, $comedy_event_id );
                update_field( 'instagram', $instagram, $comedy_event_id );
                update_field( 'website', $website, $comedy_event_id );
            }

            wp_redirect( admin_url( 'admin.php?page=excel-uploader&success=1' ) );
            exit;
        } catch (Exception $e) {
            wp_redirect( admin_url( 'admin.php?page=excel-uploader&error=1' ) );
            exit;
        }
    }
}

new Excel_Spreadsheet_Uploader();

