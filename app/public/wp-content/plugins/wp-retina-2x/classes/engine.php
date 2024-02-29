<?php

class Meow_WR2X_Engine {

    public $core = null;

    public function __construct( $core ) {
        $this->core = $core;
        add_filter( 'wp_generate_attachment_metadata', array( $this, 'wp_generate_attachment_metadata' ) );
        add_action( 'delete_attachment', array( $this, 'delete_attachment' ) );
	}

    // Resize the image
	function resize( $file_path, $width, $height, $crop, $newfile, $customCrop = false ) {
		$crop_params = $crop == '1' ? true : $crop;
		$orig_size = getimagesize( $file_path );
		$image_src = array ();
		$image_src[0] = $file_path;
		$image_src[1] = $orig_size[0];
		$image_src[2] = $orig_size[1];
		$file_info = pathinfo( $file_path );
		$newfile_info = pathinfo( $newfile );
		$extension = '.' . $newfile_info['extension'];
		$no_ext_path = $file_info['dirname'] . '/' . $file_info['filename'];
		$cropped_img_path = $no_ext_path . '-' . $width . 'x' . $height . "-tmp" . $extension;
		$image = wp_get_image_editor( $file_path );

		if ( is_wp_error( $image ) ) {
			$this->core->log( "Resize failure: " . $image->get_error_message() );
			return null;
		}

		// Resize or use Custom Crop
		if ( !$customCrop )
			$image->resize( $width, $height, $crop_params );
		else
			$image->crop(
                $customCrop['x'] * $customCrop['scale'],
                $customCrop['y'] * $customCrop['scale'],
                $customCrop['w'] * $customCrop['scale'],
                $customCrop['h'] * $customCrop['scale'],
                $width,
                $height,
                false
            );

		// Quality
		$quality = $this->core->get_option( 'quality' );
		if ( empty( $quality ) ) {
			$quality = apply_filters( 'jpeg_quality', 75 );
		}
		$image->set_quality( $quality );

		$saved = $image->save( $cropped_img_path );
		if ( is_wp_error( $saved ) ) {
			$error = $saved->get_error_message();
			trigger_error( "Retina: Could not create/resize image " . $file_path . " to " . $newfile . ": " . $error , E_USER_WARNING );
			error_log( "Retina: Could not create/resize image " . $file_path . " to " . $newfile . ":" . $error );
			return null;
		}
		if ( rename( $saved['path'], $newfile ) )
			$cropped_img_path = $newfile;
		else {
			trigger_error( "Retina: Could not move " . $saved['path'] . " to " . $newfile . "." , E_USER_WARNING );
			error_log( "Retina: Could not move " . $saved['path'] . " to " . $newfile . "." );
			return null;
		}
		$new_img_size = getimagesize( $cropped_img_path );
		$new_img = str_replace( basename( $image_src[0] ), basename( $cropped_img_path ), $image_src[0] );
		$vt_image = array ( 'url' => $new_img, 'width' => $new_img_size[0], 'height' => $new_img_size[1] );
		return $vt_image;
	}

    // -------------------------
    // Generate functions
    // -------------------------
    function wp_generate_attachment_metadata( $meta ) {
		if ( $this->core->get_option( "auto_generate" ) == true ) {
			if ( $this->core->is_image_meta( $meta ) ) {
				$this->generate_retina_images( $meta );
            }
        }
		if ( $this->core->get_option( "webp_auto_generate" ) == true ) {
			if ( $this->core->is_image_meta( $meta ) ) {
				$this->generate_webp_images( $meta );
            }
        }
        if ( $this->core->get_option( "auto_generate" ) == true && $this->core->get_option( "webp_auto_generate" ) == true ) {
            if ( $this->core->is_image_meta( $meta ) ) {
				$this->generate_webp_retina_images( $meta );
            }
        }

        return $meta;
	}

	/**
	 * @param mixed[] $meta
	 * int       width
	 * int       height
	 * string    file
	 * mixed[][] sizes
	 */
	function generate_retina_images( $meta ) {
		global $_wp_additional_image_sizes;
		$sizes = $this->core->get_image_sizes();
		if ( !isset( $meta['file'] ) ) return;

		$uploads = wp_upload_dir();

		// Check if the full-size-retina version of the generation source exists.
		// If it exists, replace the file path and its dimensions
		if ( $retina = $this->core->get_retina( $uploads['basedir'] . '/' . $meta['file'] ) ) {
			$meta['file'] = substr( $retina, strlen( $uploads['basedir'] ) + 1 );
			$dim = getimagesize( $retina );
			$meta['width']  = $dim[0];
			$meta['height'] = $dim[1];
		}

		$originalfile = $meta['file'];
		$pathinfo = pathinfo( $originalfile );
		$original_basename = $pathinfo['basename'];
		$basepath = trailingslashit( $uploads['basedir'] ) . $pathinfo['dirname'];

		// $ignore = $this->core->get_option( "ignore_sizes" );
		// if ( empty( $ignore ) )
		// 	$ignore = array();
		// $ignore = array_keys( $ignore );
		$issue = false;
		$id = $this->core->get_attachment_id( $meta['file'] );

		/**
		 * @param $id ID of the attachment whose retina image is to be generated
		 */
		do_action( 'wr2x_before_generate_retina', $id );

		$this->core->log("* GENERATE RETINA FOR ATTACHMENT '{$meta['file']}'");
		$this->core->log( "Full-Size is {$original_basename}." );

		foreach ( $sizes as $name => $attr ) {
			$normal_file = "";
			if ( !$attr['retina'] ) {
				$this->core->log( "Retina for {$name} ignored (settings)." );
				continue;
			}
			// Is the file related to this size there?
			$pathinfo = null;
			$retina_file = null;

			if ( isset( $meta['sizes'][$name] ) && isset( $meta['sizes'][$name]['file'] ) ) {
				$normal_file = trailingslashit( $basepath ) . $meta['sizes'][$name]['file'];
				$pathinfo = pathinfo( $normal_file ) ;
				$retina_file = trailingslashit( $pathinfo['dirname'] ) . $pathinfo['filename'] . $this->core->retina_extension() . $pathinfo['extension'];
			}

			if ( $retina_file && file_exists( $retina_file ) ) {
				$this->core->log( "Base for {$name} is '{$normal_file }'." );
				$this->core->log( "Retina for {$name} already exists: '$retina_file'." );
				continue;
			}
			if ( $retina_file ) {
				$originalfile = trailingslashit( $pathinfo['dirname'] ) . $original_basename;

				if ( !file_exists( $originalfile ) ) {
					$this->core->log( "[ERROR] Original file '{$originalfile}' cannot be found." );
					return $meta;
				}

				// Maybe that new image is exactly the size of the original image.
				// In that case, let's make a copy of it.
				if ( $meta['sizes'][$name]['width'] * 2 == $meta['width'] && $meta['sizes'][$name]['height'] * 2 == $meta['height'] ) {
					copy( $originalfile, $retina_file );
					$this->core->log( "Retina for {$name} created: '{$retina_file}' (as a copy of the full-size)." );
				}
				// Otherwise let's resize (if the original size is big enough).
				else if ( $this->core->are_dimensions_ok( $meta['width'], $meta['height'], $meta['sizes'][$name]['width'] * 2, $meta['sizes'][$name]['height'] * 2 ) ) {
					// Change proposed by Nicscott01, slighlty modified by Jordy (+isset)
					// (https://wordpress.org/support/topic/issue-with-crop-position?replies=4#post-6200271)
					$crop = isset( $_wp_additional_image_sizes[$name] ) ? $_wp_additional_image_sizes[$name]['crop'] : true;
					$customCrop = apply_filters( 'wr2x_custom_crop', null, $id, $name );

					// // Support for Manual Image Crop
					// // If the size of the image was manually cropped, let's keep it.
					// if ( class_exists( 'ManualImageCrop' ) && isset( $meta['micSelectedArea'] ) && isset( $meta['micSelectedArea'][$name] ) && isset( $meta['micSelectedArea'][$name]['scale'] ) ) {
					// 	$customCrop = $meta['micSelectedArea'][$name];
					// }

					$image = $this->resize( $originalfile, $meta['sizes'][$name]['width'] * 2,
						$meta['sizes'][$name]['height'] * 2, $crop, $retina_file, $customCrop );
				}
				if ( !file_exists( $retina_file ) ) {
					$is_ok = apply_filters( "wr2x_last_chance_generate", false, $id, $retina_file,
						$meta['sizes'][$name]['width'] * 2, $meta['sizes'][$name]['height'] * 2 );
					if ( !$is_ok ) {
						$this->core->log( "[ERROR] Retina for {$name} could not be created. Full-Size is " . $meta['width'] . "x" . $meta['height'] . " but Retina requires a file of at least " . $meta['sizes'][$name]['width'] * 2 . "x" . $meta['sizes'][$name]['height'] * 2 . "." );
						$issue = true;
					}
				}
				else {
					do_action( 'wr2x_retina_file_added', $id, $retina_file, $name );
					$this->core->log( "Retina for {$name} created: '{$retina_file}'." );
				}
			} else {
				if ( empty( $normal_file ) )
					$this->core->log( "[ERROR] Base file for '{$name}' does not exist." );
				else
					$this->core->log( "[ERROR] Base file for '{$name}' cannot be found here: '{$normal_file}'." );
			}
		}

		// Checks attachment ID + issues
		if ( !$id )
			return $meta;
		if ( $issue )
			$this->core->add_issue( $id );
		else
			$this->core->remove_issue( $id );

		/**
		 * @param $id ID of the attachment whose retina image has been generated
		 */
		do_action( 'wr2x_generate_retina', $id );

		return $meta;
	}

	/**
	 * @param mixed[] $meta
	 * int       width
	 * int       height
	 * string    file
	 * mixed[][] sizes
	 */
	function generate_webp_images( $meta ) {
        if ( !extension_loaded( 'gd' ) && !extension_loaded( 'imagick' ) ) {
            $this->core->log("Can not create WebP images. Neither GD nor Imagick are installed.");
            return;
        }
        if ( !isset( $meta['file'] ) ) return;

        global $_wp_additional_image_sizes;
		$sizes = $this->core->get_image_sizes();
		$uploads = wp_upload_dir();

		// Check if the full-size-webp version of the generation source exists.
		// If it exists, replace the file path and its dimensions
		if ( $webp = $this->core->get_webp( $uploads['basedir'] . '/' . $meta['file'] ) ) {
			$meta['file'] = substr( $webp, strlen( $uploads['basedir'] ) + 1 );
			$dim = getimagesize( $webp );
			$meta['width']  = $dim[0];
			$meta['height'] = $dim[1];
		}

		$originalfile = $meta['file'];
		$pathinfo = pathinfo( $originalfile );
		$original_basename = $pathinfo['basename'];
		$basepath = trailingslashit( $uploads['basedir'] ) . $pathinfo['dirname'];

		$issue = false;
		$id = $this->core->get_attachment_id( $meta['file'] );

		/**
		 * @param $id ID of the attachment whose web image is to be generated
		 */
		do_action( 'wr2x_before_generate_webp', $id );

		$this->core->log("* GENERATE WEBP FOR ATTACHMENT '{$meta['file']}'");
		$this->core->log( "Full-Size is {$original_basename}." );

		foreach ( $sizes as $name => $attr ) {
			$normal_file = "";
			if ( !$attr['webp'] ) {
				$this->core->log( "WebP for {$name} ignored (settings)." );
				continue;
			}
			// Is the file related to this size there?
			$pathinfo = null;
			$webp_file = null;

			if ( isset( $meta['sizes'][$name] ) && isset( $meta['sizes'][$name]['file'] ) ) {
				$normal_file = trailingslashit( $basepath ) . $meta['sizes'][$name]['file'];
				$pathinfo = pathinfo( $normal_file ) ;

				$new_webp_ext = $pathinfo['extension'] === 'webp' ? '' : $this->core->webp_extension();
				$webp_file = trailingslashit( $pathinfo['dirname'] ) . $pathinfo['filename'] . "." . $pathinfo['extension'] . $new_webp_ext;
			}

			if ( $webp_file && file_exists( $webp_file ) ) {
				$this->core->log( "Base for {$name} is '{$normal_file }'." );
				$this->core->log( "WebP for {$name} already exists: '$webp_file'." );
				continue;
			}
			if ( $webp_file ) {
				$originalfile = trailingslashit( $pathinfo['dirname'] ) . $original_basename;

				if ( !file_exists( $originalfile ) ) {
					$this->core->log( "[ERROR] Original file '{$originalfile}' cannot be found." );
					return $meta;
				}

				// Generate WebP file by using GD or Imagick.
                // Change proposed by Nicscott01, slighlty modified by Jordy (+isset)
                // (https://wordpress.org/support/topic/issue-with-crop-position?replies=4#post-6200271)
                $crop = isset( $_wp_additional_image_sizes[$name] ) ? $_wp_additional_image_sizes[$name]['crop'] : true;
                $customCrop = apply_filters( 'wr2x_custom_crop', null, $id, $name );

                $this->resize( $originalfile, $meta['sizes'][$name]['width'],
                    $meta['sizes'][$name]['height'], $crop, $webp_file, $customCrop );

				if ( !file_exists( $webp_file ) ) {
					$this->core->log( "[ERROR] WebP for {$name} could not be created.");
					$issue = true;
				}
				else {
					do_action( 'wr2x_webp_file_added', $id, $webp_file, $name );
					$this->core->log( "WebP for {$name} created: '{$webp_file}'." );
				}
			} else {
				if ( empty( $normal_file ) )
					$this->core->log( "[ERROR] Base file for '{$name}' does not exist." );
				else
					$this->core->log( "[ERROR] Base file for '{$name}' cannot be found here: '{$normal_file}'." );
			}
		}

		// Checks attachment ID + issues
		if ( !$id )
			return $meta;
		if ( $issue )
			$this->core->add_issue( $id );
		else
			$this->core->remove_issue( $id );

		/**
		 * @param $id ID of the attachment whose retina image has been generated
		 */
		do_action( 'wr2x_generate_webp', $id );

		return $meta;
	}

    /**
	 * @param mixed[] $meta
	 * int       width
	 * int       height
	 * string    file
	 * mixed[][] sizes
	 */
	function generate_webp_retina_images( $meta ) {
        if ( !extension_loaded( 'gd' ) && !extension_loaded( 'imagick' ) ) {
            $this->core->log("Can not create WebP images. Neither GD nor Imagick are installed.");
            return;
        }
		if ( !isset( $meta['file'] ) ) return;

        global $_wp_additional_image_sizes;
		$sizes = $this->core->get_image_sizes();

		$uploads = wp_upload_dir();

		// Check if the full-size-retina version of the generation source exists.
		// If it exists, replace the file path and its dimensions
		if ( $retina = $this->core->get_retina( $uploads['basedir'] . '/' . $meta['file'] ) ) {
			$meta['file'] = substr( $retina, strlen( $uploads['basedir'] ) + 1 );
			$dim = getimagesize( $retina );
			$meta['width']  = $dim[0];
			$meta['height'] = $dim[1];
		}

		$originalfile = $meta['file'];
		$pathinfo = pathinfo( $originalfile );
		$original_basename = $pathinfo['basename'];
		$basepath = trailingslashit( $uploads['basedir'] ) . $pathinfo['dirname'];

		$issue = false;
		$id = $this->core->get_attachment_id( $meta['file'] );

		/**
		 * @param $id ID of the attachment whose retina image is to be generated
		 */
		do_action( 'wr2x_before_generate_webp_retina', $id );

		$this->core->log("* GENERATE WEBP RETINA FOR ATTACHMENT '{$meta['file']}'");
		$this->core->log( "Full-Size is {$original_basename}." );

		foreach ( $sizes as $name => $attr ) {
			$normal_file = "";
			if ( !$attr['webp_retina'] ) {
				$this->core->log( "WebP Retina for {$name} ignored (settings)." );
				continue;
			}
			// Is the file related to this size there?
			$pathinfo = null;
			$webp_retina_file = null;

			if ( isset( $meta['sizes'][$name] ) && isset( $meta['sizes'][$name]['file'] ) ) {
				$normal_file = trailingslashit( $basepath ) . $meta['sizes'][$name]['file'];
				$pathinfo = pathinfo( $normal_file ) ;

				$new_webp_ext = $pathinfo['extension'] === 'webp' ? '' : $this->core->webp_extension();
				$webp_retina_file = trailingslashit( $pathinfo['dirname'] ) . $pathinfo['filename'] . $this->core->retina_extension() . $pathinfo['extension'] . $new_webp_ext;
			}

			if ( $webp_retina_file && file_exists( $webp_retina_file ) ) {
				$this->core->log( "Base for {$name} is '{$normal_file }'." );
				$this->core->log( "WebP Retina for {$name} already exists: '$webp_retina_file'." );
				continue;
			}
			if ( $webp_retina_file ) {
				$originalfile = trailingslashit( $pathinfo['dirname'] ) . $original_basename;

				if ( !file_exists( $originalfile ) ) {
					$this->core->log( "[ERROR] Original file '{$originalfile}' cannot be found." );
					return $meta;
				}

				// Resize (if the original size is big enough).
				if ( $this->core->are_dimensions_ok( $meta['width'], $meta['height'], $meta['sizes'][$name]['width'] * 2, $meta['sizes'][$name]['height'] * 2 ) ) {
					// Change proposed by Nicscott01, slighlty modified by Jordy (+isset)
					// (https://wordpress.org/support/topic/issue-with-crop-position?replies=4#post-6200271)
					$crop = isset( $_wp_additional_image_sizes[$name] ) ? $_wp_additional_image_sizes[$name]['crop'] : true;
					$customCrop = apply_filters( 'wr2x_custom_crop', null, $id, $name );

					$this->resize( $originalfile, $meta['sizes'][$name]['width'] * 2,
						$meta['sizes'][$name]['height'] * 2, $crop, $webp_retina_file, $customCrop );
				}
				if ( !file_exists( $webp_retina_file ) ) {
					$this->core->log( "[ERROR] WebP Retina for {$name} could not be created. Full-Size is " . $meta['width'] . "x" . $meta['height'] . " but WebP Retina requires a file of at least " . $meta['sizes'][$name]['width'] * 2 . "x" . $meta['sizes'][$name]['height'] * 2 . "." );
					$issue = true;
				}
				else {
					do_action( 'wr2x_webp_retina_file_added', $id, $webp_retina_file, $name );
					$this->core->log( "WebP Retina for {$name} created: '{$webp_retina_file}'." );
				}
			} else {
				if ( empty( $normal_file ) )
					$this->core->log( "[ERROR] Base file for '{$name}' does not exist." );
				else
					$this->core->log( "[ERROR] Base file for '{$name}' cannot be found here: '{$normal_file}'." );
			}
		}

		// Checks attachment ID + issues
		if ( !$id )
			return $meta;
		if ( $issue )
			$this->core->add_issue( $id );
		else
			$this->core->remove_issue( $id );

		/**
		 * @param $id ID of the attachment whose retina image has been generated
		 */
		do_action( 'wr2x_generate_webp_retina', $id );

		return $meta;
	}

    // -------------------------
    // Delete functions
    // -------------------------
    function delete_attachment( $attach_id, $deleteFullSize = true) {
        $meta = wp_get_attachment_metadata( $attach_id );
        $this->delete_retina_images( $meta, $deleteFullSize );
        $this->delete_webp_images( $meta, $deleteFullSize );
        $this->delete_webp_retina_images( $meta );
		$this->core->remove_issue( $attach_id );
    }

    function delete_retina_attachment( $attach_id, $deleteFullSize = true ) {
		$meta = wp_get_attachment_metadata( $attach_id );
		$this->delete_retina_images( $meta, $deleteFullSize );
		$this->core->remove_issue( $attach_id );
	}

    function delete_webp_attachment( $attach_id, $deleteFullSize = true ) {
		$meta = wp_get_attachment_metadata( $attach_id );
		$this->delete_webp_images( $meta, $deleteFullSize );
		$this->core->remove_issue( $attach_id );
	}

    function delete_webp_retina_attachment( $attach_id ) {
		$meta = wp_get_attachment_metadata( $attach_id );
		$this->delete_webp_retina_images( $meta );
		$this->core->remove_issue( $attach_id );
	}

    function delete_retina_images( $meta, $deleteFullSize = false ) {
		if ( !$this->core->is_image_meta( $meta ) )
			return $meta;
		$sizes = $meta['sizes'];
		if ( !$sizes || !is_array( $sizes ) )
			return $meta;
		$this->core->log("* DELETE RETINA FOR ATTACHMENT '{$meta['file']}'");
		$originalfile = $meta['file'];
		$id = $this->core->get_attachment_id( $originalfile );
		$pathinfo = pathinfo( $originalfile );
		$uploads = wp_upload_dir();
		$basepath = trailingslashit( $uploads['basedir'] ) . $pathinfo['dirname'];
		foreach ( $sizes as $name => $attr ) {
			$pathinfo = pathinfo( $attr['file'] );
			$retina_file = $pathinfo['filename'] . $this->core->retina_extension() . $pathinfo['extension'];
			if ( file_exists( trailingslashit( $basepath ) . $retina_file ) ) {
				$fullpath = trailingslashit( $basepath ) . $retina_file;
				unlink( $fullpath );
				do_action( 'wr2x_retina_file_removed', $id, $retina_file );
				$this->core->log("Deleted '$fullpath'.");
			}
		}
		// Remove full-size if there is any
		if ( $deleteFullSize ) {
			$pathinfo = pathinfo( $originalfile );
			$retina_file = $pathinfo[ 'filename' ] . $this->core->retina_extension() . $pathinfo[ 'extension' ];
			if ( file_exists( trailingslashit( $basepath ) . $retina_file ) ) {
				$fullpath = trailingslashit( $basepath ) . $retina_file;
				unlink( $fullpath );
				do_action( 'wr2x_retina_file_removed', $id, $retina_file );
				$this->core->log( "Deleted '$fullpath'." );
			}
		}
		return $meta;
	}

    function delete_webp_images( $meta, $deleteFullSize = false ) {
		if ( !$this->core->is_image_meta( $meta ) )
			return $meta;
		$sizes = $meta['sizes'];
		if ( !$sizes || !is_array( $sizes ) )
			return $meta;
		$this->core->log("* DELETE WEBP FOR ATTACHMENT '{$meta['file']}'");
		$originalfile = $meta['file'];
		$id = $this->core->get_attachment_id( $originalfile );
		$pathinfo = pathinfo( $originalfile );
		$uploads = wp_upload_dir();
		$basepath = trailingslashit( $uploads['basedir'] ) . $pathinfo['dirname'];
		foreach ( $sizes as $attr ) {
			$pathinfo = pathinfo( $attr['file'] );
			$webp_file = $pathinfo['filename'] . '.' . $pathinfo['extension'] . $this->core->webp_extension();
			if ( file_exists( trailingslashit( $basepath ) . $webp_file ) ) {
				$fullpath = trailingslashit( $basepath ) . $webp_file;
				unlink( $fullpath );
				do_action( 'wr2x_webp_file_removed', $id, $webp_file );
				$this->core->log("Deleted '$fullpath'.");
			}
		}
		// Remove full-size if there is any
		if ( $deleteFullSize ) {
			$pathinfo = pathinfo( $originalfile );
			$webp_file = $pathinfo[ 'filename' ] . '.' . $pathinfo[ 'extension' ] . $this->core->webp_extension();
			if ( file_exists( trailingslashit( $basepath ) . $webp_file ) ) {
				$fullpath = trailingslashit( $basepath ) . $webp_file;
				unlink( $fullpath );
				do_action( 'wr2x_webp_file_removed', $id, $webp_file );
				$this->core->log( "Deleted '$fullpath'." );
			}
		}
		return $meta;
	}

    function delete_webp_retina_images( $meta ) {
		if ( !$this->core->is_image_meta( $meta ) )
			return $meta;
		$sizes = $meta['sizes'];
		if ( !$sizes || !is_array( $sizes ) )
			return $meta;
		$this->core->log("* DELETE WEBP RETINA FOR ATTACHMENT '{$meta['file']}'");
		$originalfile = $meta['file'];
		$id = $this->core->get_attachment_id( $originalfile );
		$pathinfo = pathinfo( $originalfile );
		$uploads = wp_upload_dir();
		$basepath = trailingslashit( $uploads['basedir'] ) . $pathinfo['dirname'];
		foreach ( $sizes as $name => $attr ) {
			$pathinfo = pathinfo( $attr['file'] );
			$retina_file = $pathinfo['filename'] . $this->core->retina_extension() . $pathinfo['extension'] . $this->core->webp_extension();
			if ( file_exists( trailingslashit( $basepath ) . $retina_file ) ) {
				$fullpath = trailingslashit( $basepath ) . $retina_file;
				unlink( $fullpath );
				do_action( 'wr2x_webp_retina_file_removed', $id, $retina_file );
				$this->core->log("Deleted '$fullpath'.");
			}
		}
		return $meta;
	}

	// This is called by functions in the REST API
	// TODO: However, this function seems to be what delete_retina_images does above, 
	// so maybe we could optimize and avoid code redundancy.
	function delete_retina_fullsize( $mediaId ) {
		$originalfile = get_attached_file( $mediaId );
		$pathinfo = pathinfo( $originalfile );
		$retina_file = trailingslashit( $pathinfo['dirname'] ) . $pathinfo['filename'] . $this->core->retina_extension() . $pathinfo['extension'];
		if ( $retina_file && file_exists( $retina_file ) ) {
			return unlink( $retina_file );
		}
		return false;
	}
	function delete_webp_fullsize( $mediaId ) {
		$originalfile = get_attached_file( $mediaId );
		$pathinfo = pathinfo( $originalfile );
		$retina_file = trailingslashit( $pathinfo['dirname'] ) . $pathinfo['filename'] . '.' . $pathinfo['extension'] . $this->core->webp_extension();
		if ( $retina_file && file_exists( $retina_file ) ) {
			return unlink( $retina_file );
		}
		return false;
	}
}