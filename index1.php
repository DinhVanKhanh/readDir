<?php
    function convertASPToPHP( $directory ) {
        $scdir = scandir( $directory );
        $skips = [
            ".",
            "..",
            "images",
            "index1.php",
            // "index.php",
            "data",
            "images_general",
            "lib",
            "case",
            "css",
            "js",
            "minzei",
        ];

        foreach ( $scdir as $key => $dfile ) {
            if ( !in_array( $dfile, $skips ) ) {
                $path =  $directory . DIRECTORY_SEPARATOR . $dfile;
                if ( is_dir( $path ) ) {
                    convertASPToPHP( $path );
                    continue;
                }

                $file     = explode( ".", $dfile );
                $filename = $file[0];
                $ext      = end( $file );
                $newFile  = $directory . DIRECTORY_SEPARATOR . $file[0] . ".php";

                if ( is_file( $path ) && $ext == "asp" ) {
                    $file = file( $path, FILE_SKIP_EMPTY_LINES | FILE_IGNORE_NEW_LINES );
                    foreach ( $file as $index => $line ) {
                        // Skip <%@ LANGUAGE="VBScript" %>
                        preg_match( "/^.+(LANGUAGE=\"VBScript\").+/i", $line, $matched, PREG_OFFSET_CAPTURE );
                        $count = count( $matched );
                        if ( $count > 0 ) {
                            continue;
                        }

                        // Skip <% Option Explicit %>
                        preg_match( "/^.+Option\s+Explicit.+/i", $line, $matched, PREG_OFFSET_CAPTURE );
                        $count = count( $matched );
                        if ( $count > 0 ) {
                            continue;
                        }

                        // Syntax PHP
                        if ( strpos( "<%", $line ) !== false || strpos( "%>", $line ) !== false ) {
                            if ( strpos( "<%=", $line ) !== false ) {
                                $line = str_replace( "<%=", "<?=", $line );
                            }
                            $line = str_replace( "<%", "<?php", $line );
                            $line = str_replace( "%>", "?>", $line );
                        }

                        // Import file
                        $line = preg_replace( "/<!--\s+#include\s+virtual\s+=\s+/i", "<?php require_once __DIR__ . ", $line );
                        $line = preg_replace( "/\.inc\"\s+-->/i", ".php\"; ?>", $line );

                        // lib/global_navi.inc
                        $line = str_replace( "lib/global_navi.php", "/../../lib/global_navi.php", $line );

                        // lib/webserver_flg.inc
                        $line = str_replace( "lib/webserver_flg.php", "/../../../common_files/webserver_flg.php", $line );

                        // products_gyou/lib/bs_products.inc
                        $line = str_replace( "products_gyou/lib/bs_products.php", "/../../../common_files/products_version.php", $line );

                        // products/lib/af_products.inc
                        $line = str_replace( "products/lib/af_products.php", "/../../../common_files/products_version.php", $line );

                        // /ssi/case-link-farm.inc
                        $line = str_replace( "/ssi/case-link-farm.php", "/../../ssi/case-link-farm.php", $line );

                        // /ssi/global.inc
                        $line = str_replace( "/ssi/global.php", "/../../ssi/global.php", $line );

                        // Const  HeaderSelectNo = 0
                        $line = str_replace( "Const  HeaderSelectNo = 0", "    \$headerSelectNo = 0;", $line );

                        // Const  LocationCategory = "web"
                        $line = str_replace( "Const  LocationCategory = \"web\"", "    \$locationCategory = \"web\";", $line );

                        // Const  LocationPage = "index"
                        $line = str_replace( "Const  LocationPage = \"index\"", "    \$locationPage = \"index\";", $line );

                        // Link | File
                        $line = str_replace( ".php", ".php", $line );

                        // Charset HTML
                        $line = str_replace(['charset="Shift_JIS"','charset="shift_JIS"','charset="SHIFT_JIS"','charset="shift_jis"','charset="Shift_Jis"'], 'charset="UTF-8"', $line);

                        // Convert encoding shift-jis to utf-8
                        if ( !mb_detect_encoding( $line, 'UTF-8', true ) ) {
                            $line = iconv("SHIFT-JIS", "UTF-8//IGNORE", $line);
                        }
                        file_put_contents( $newFile, $line . "\r\n", FILE_APPEND | LOCK_EX );
                    }
                    $file = null;
                    unlink( $path );
                }
            }
        }
    }

    convertASPToPHP( realpath( __DIR__ . "/products_gyou" ) );
?>