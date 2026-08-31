<?php
namespace RankOut_Connector\Tools\Media;

use RankOut_Connector\Tools\Base_Tool;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}










































class Upload_Media_From_Url extends Base_Tool {

    const HTTP_TIMEOUT_SECONDS = 30;

    public function get_name() {
        return 'wp_upload_media_from_url';
    }

    public function get_description() {
        return 'Downloads a file from a public HTTPS URL and imports it into the WordPress media library. Required: `url` (http/https only — local/private IPs blocked via DNS-resolved SSRF check). Optional: `filename` (defaults to URL basename), `title`, `alt_text`, `caption`, `post_id` (attach to a parent post). Size limit: site `wp_max_upload_size()`. Returns { id, source_url, mime_type, title, file_size }. Avoids the ~33% base64 overhead of wp_upload_media.';
    }

    public function get_category() {
        return 'media';
    }

    public function get_required_capability() {
        return 'upload_files';
    }

    public function get_annotations() {
        return array(
            'title'           => $this->get_title(),
            'readOnlyHint'    => false,
            'destructiveHint' => false,
            'openWorldHint'   => true, 
        );
    }

    public function get_input_schema() {
        return array(
            'type'       => 'object',
            'properties' => array(
                'url'      => array(
                    'type'        => 'string',
                    'description' => 'Public http/https URL to the file. Private/internal IPs are rejected.',
                ),
                'filename' => array(
                    'type'        => 'string',
                    'description' => 'Optional filename override (defaults to URL basename).',
                ),
                'title'    => array(
                    'type'        => 'string',
                    'description' => 'Optional attachment title.',
                ),
                'alt_text' => array(
                    'type'        => 'string',
                    'description' => 'Optional alt text for accessibility.',
                ),
                'caption'  => array(
                    'type'        => 'string',
                    'description' => 'Optional caption (stored as post_excerpt).',
                ),
                'post_id'  => array(
                    'type'        => 'integer',
                    'description' => 'Optional parent post ID to attach the media to.',
                ),
            ),
            'required'   => array( 'url' ),
        );
    }

    public function execute( array $arguments ) {
        $this->validate_required( $arguments, array( 'url' ) );

        $url = $this->validate_remote_url( (string) $arguments['url'] );

        $max_bytes = wp_max_upload_size();

        
        
        
        
        
        
        
        $head_validated_size = false;

        
        
        $head = wp_safe_remote_head(
            $url,
            array(
                'timeout'     => self::HTTP_TIMEOUT_SECONDS,
                'redirection' => 5,
            )
        );
        if ( ! is_wp_error( $head ) ) {
            $code = (int) wp_remote_retrieve_response_code( $head );
            if ( $code >= 200 && $code < 400 ) {
                $declared_len = (int) wp_remote_retrieve_header( $head, 'content-length' );
                if ( $declared_len > 0 ) {
                    if ( $declared_len > $max_bytes ) {
                        throw new \InvalidArgumentException(
                            sprintf( 'Remote file too large (%s). Maximum upload size is %s.', size_format( $declared_len ), size_format( $max_bytes ) ) // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
                        );
                    }
                    
                    
                    $head_validated_size = true;
                }
            }
        }

        
        
        
        $filename = '';
        if ( ! empty( $arguments['filename'] ) ) {
            $filename = sanitize_file_name( (string) $arguments['filename'] );
        }
        if ( '' === $filename ) {
            $path     = (string) wp_parse_url( $url, PHP_URL_PATH );
            $filename = sanitize_file_name( basename( $path ) );
        }
        if ( '' === $filename ) {
            $filename = 'remote-' . wp_generate_password( 8, false ) . '.bin';
        }

        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/image.php';
        require_once ABSPATH . 'wp-admin/includes/media.php';

        $tmp_file = wp_tempnam( $filename );
        if ( ! $tmp_file ) {
            throw new \RuntimeException( 'Could not create temporary file.' );
        }

        
        
        
        
        $response = wp_safe_remote_get(
            $url,
            array(
                'timeout'             => self::HTTP_TIMEOUT_SECONDS,
                'redirection'         => 5,
                'limit_response_size' => $max_bytes + 1,
                'stream'              => true,
                'filename'            => $tmp_file,
            )
        );
        if ( is_wp_error( $response ) ) {
            \wp_delete_file( $tmp_file );
            throw new \RuntimeException(
                sprintf( 'Could not fetch remote URL: %s', $response->get_error_message() ) // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
            );
        }
        $status = (int) wp_remote_retrieve_response_code( $response );
        if ( $status < 200 || $status >= 300 ) {
            \wp_delete_file( $tmp_file );
            throw new \RuntimeException(
                sprintf( 'Remote URL returned HTTP %d.', $status ) // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
            );
        }

        
        
        // phpcs:ignore WordPress.WP.AlternativeFunctions.filesize_filesize
        $body_len = (int) @filesize( $tmp_file );
        if ( $body_len <= 0 ) {
            \wp_delete_file( $tmp_file );
            throw new \RuntimeException( 'Remote URL returned an empty body.' );
        }
        if ( $body_len > $max_bytes ) {
            \wp_delete_file( $tmp_file );
            throw new \InvalidArgumentException(
                sprintf( 'Remote file too large at %s. Maximum upload size is %s.', size_format( $body_len ), size_format( $max_bytes ) ) // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
            );
        }
        unset( $head_validated_size ); 

        $check = wp_check_filetype_and_ext( $tmp_file, $filename );
        if ( empty( $check['type'] ) ) {
            \wp_delete_file( $tmp_file );
            throw new \InvalidArgumentException( 'File type is not allowed for upload.' );
        }
        
        
        if ( ! empty( $check['proper_filename'] ) ) {
            $filename = $check['proper_filename'];
        }

        $file_array = array(
            'name'     => $filename,
            'tmp_name' => $tmp_file,
            'type'     => $check['type'],
            'size'     => $body_len,
        );

        $parent_post_id = isset( $arguments['post_id'] ) ? (int) $arguments['post_id'] : 0;
        if ( $parent_post_id < 0 ) {
            $parent_post_id = 0;
        }

        
        
        
        
        
        if ( $parent_post_id > 0 && ! current_user_can( 'edit_post', $parent_post_id ) ) {
            \wp_delete_file( $tmp_file );
            throw new \RuntimeException(
                sprintf( 'You do not have permission to attach media to post %d.', $parent_post_id ) // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
            );
        }

        
        
        
        
        $attachment_id = media_handle_sideload( $file_array, $parent_post_id );
        if ( \is_wp_error( $attachment_id ) ) {
            \wp_delete_file( $tmp_file );
            throw new \RuntimeException( $attachment_id->get_error_message() ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
        }

        
        
        
        
        $post_update = array( 'ID' => $attachment_id );
        if ( array_key_exists( 'title', $arguments ) ) {
            $post_update['post_title'] = sanitize_text_field( (string) $arguments['title'] );
        }
        if ( array_key_exists( 'caption', $arguments ) ) {
            $post_update['post_excerpt'] = sanitize_text_field( (string) $arguments['caption'] );
        }
        if ( count( $post_update ) > 1 ) {
            wp_update_post( $post_update );
        }
        if ( array_key_exists( 'alt_text', $arguments ) ) {
            update_post_meta( $attachment_id, '_wp_attachment_image_alt', sanitize_text_field( (string) $arguments['alt_text'] ) );
        }

        $attachment = get_post( $attachment_id );

        return array(
            'id'         => (int) $attachment_id,
            'source_url' => wp_get_attachment_url( $attachment_id ),
            'mime_type'  => $attachment ? (string) $attachment->post_mime_type : (string) $check['type'],
            'title'      => $attachment ? (string) $attachment->post_title : '',
            'file_size'  => $body_len,
        );
    }

    













    private function validate_remote_url( $url ) {
        $url    = trim( $url );
        $parsed = wp_parse_url( $url );

        if ( ! $parsed || empty( $parsed['host'] ) ) {
            throw new \InvalidArgumentException( 'Invalid URL.' );
        }
        $scheme = strtolower( isset( $parsed['scheme'] ) ? $parsed['scheme'] : '' );
        if ( ! in_array( $scheme, array( 'http', 'https' ), true ) ) {
            throw new \InvalidArgumentException( 'URL must use http or https.' );
        }
        if ( ! empty( $parsed['user'] ) || ! empty( $parsed['pass'] ) ) {
            throw new \InvalidArgumentException( 'URLs containing userinfo are not allowed.' );
        }

        $host = $parsed['host'];
        if ( '' === $host ) {
            throw new \InvalidArgumentException( 'URL host is empty.' );
        }
        
        if ( '[' === $host[0] ) {
            $host = trim( $host, '[]' );
        }

        
        if ( filter_var( $host, FILTER_VALIDATE_IP ) ) {
            $this->reject_if_unsafe_ip( $host );
            return esc_url_raw( $url );
        }

        
        $resolved = $this->resolve_host( $host );
        if ( empty( $resolved ) ) {
            throw new \InvalidArgumentException(
                sprintf( 'Could not resolve host: %s', $host ) // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
            );
        }
        foreach ( $resolved as $ip ) {
            $this->reject_if_unsafe_ip( $ip );
        }

        return esc_url_raw( $url );
    }

    


















    private function resolve_host( $host ) {
        if ( ! function_exists( 'dns_get_record' ) ) {
            throw new \RuntimeException(
                'Cannot validate remote URL: dns_get_record() is unavailable on this PHP install. Re-enable it (it ships with PHP since 5.3) before using wp_upload_media_from_url.'
            );
        }

        $ips = array();

        
        
        $records = @dns_get_record( $host, DNS_A | DNS_AAAA ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
        if ( ! is_array( $records ) ) {
            return $ips;
        }
        foreach ( $records as $rec ) {
            $type = isset( $rec['type'] ) ? $rec['type'] : '';
            if ( 'A' === $type && ! empty( $rec['ip'] ) ) {
                $ips[] = (string) $rec['ip'];
            } elseif ( 'AAAA' === $type && ! empty( $rec['ipv6'] ) ) {
                $ips[] = (string) $rec['ipv6'];
            }
        }
        return $ips;
    }

    











    private function reject_if_unsafe_ip( $ip ) {
        if ( ! filter_var( $ip, FILTER_VALIDATE_IP ) ) {
            throw new \InvalidArgumentException(
                sprintf( 'Invalid IP address: %s', $ip ) // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
            );
        }

        
        if ( filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6 ) && 0 === stripos( $ip, '::ffff:' ) ) {
            $maybe_v4 = substr( $ip, 7 );
            if ( filter_var( $maybe_v4, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 ) ) {
                $ip = $maybe_v4;
            }
        }

        $is_public = filter_var(
            $ip,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
        );
        if ( false === $is_public ) {
            throw new \InvalidArgumentException(
                'URL resolves to a private / reserved IP range and cannot be fetched.'
            );
        }
    }
}
