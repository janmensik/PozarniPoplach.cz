<?php

/*
 * Smarty plugin "Thumb"
 * Purpose: creates cached thumbnails
 * Home: http://www.cerdmann.com/thumb/
 * Copyright (C) 2005 Christoph Erdmann
 *
 * This library is free software; you can redistribute it and/or modify it under the terms of the GNU Lesser General Public License as published by the Free Software Foundation; either version 2.1 of the License, or (at your option) any later version.
 *
 * This library is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License along with this library; if not, write to the Free Software Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA 02110, USA
 * -------------------------------------------------------------
 * Author:   Christoph Erdmann (CE)
 * Internet: http://www.cerdmann.com
 *
 * Author: Benjamin Fleckenstein (BF)
 * Internet: http://www.benjaminfleckenstein.de
 *
 * Author: Marcus Gueldenmeister (MG)
 * Internet: http://www.gueldenmeister.de/marcus/
 *
 * Author: Andreas Bsch (AB)
 */
function smarty_function_thumb(array|null $params, \Smarty\Template $template) {
    // Start time measurement
    if ($params['dev']) {
        if (!function_exists('getmicrotime')) {
            function getmicrotime() {
                list($usec, $sec) = explode(" ", microtime());
                return ((float)$usec + (float)$sec);
            }
        }
        $time['start'] = getmicrotime();
    }

    // Function for sharpening
    if (!function_exists('UnsharpMask')) {
        // Unsharp mask algorithm by Torstein Hnsi 2003 (thoensi_at_netcom_dot_no)
        // Christoph Erdmann: changed it a little, cause i could not reproduce the darker blurred image, now it is up to 15% faster with same results
        function UnsharpMask(GdImage $img, int $amount, int $radius, int $threshold) {
            // Attempt to calibrate the parameters to Photoshop:
            if ($amount > 500) {
                $amount = 500;
            }
            $amount = $amount * 0.016;
            if ($radius > 50) {
                $radius = 50;
            }
            $radius = $radius * 2;
            if ($threshold > 255) {
                $threshold = 255;
            }

            $radius = abs(round($radius));  // Only integers make sense.
            if ($radius == 0) {
                return $img;
                // break;
            }
            $w = imagesx($img);
            $h = imagesy($img);
            $imgCanvas = $img;
            $imgCanvas2 = $img;
            $imgBlur = imagecreatetruecolor($w, $h);

            // Gaussian blur matrix:
            //  1   2   1
            //  2   4   2
            //  1   2   1

            // Move copies of the image around one pixel at the time and merge them with weight
            // according to the matrix. The same matrix is simply repeated for higher radii.
            for ($i = 0; $i < $radius; $i++) {
                imagecopy($imgBlur, $imgCanvas, 0, 0, 1, 1, $w - 1, $h - 1); // up left
                imagecopymerge($imgBlur, $imgCanvas, 1, 1, 0, 0, $w, $h, 50); // down right
                imagecopymerge($imgBlur, $imgCanvas, 0, 1, 1, 0, $w - 1, $h, 33); // down left
                imagecopymerge($imgBlur, $imgCanvas, 1, 0, 0, 1, $w, $h - 1, 25); // up right
                imagecopymerge($imgBlur, $imgCanvas, 0, 0, 1, 0, $w - 1, $h, 33); // left
                imagecopymerge($imgBlur, $imgCanvas, 1, 0, 0, 0, $w, $h, 25); // right
                imagecopymerge($imgBlur, $imgCanvas, 0, 0, 0, 1, $w, $h - 1, 20); // up
                imagecopymerge($imgBlur, $imgCanvas, 0, 1, 0, 0, $w, $h, 16); // down
                imagecopymerge($imgBlur, $imgCanvas, 0, 0, 0, 0, $w, $h, 50); // center
            }
            $imgCanvas = $imgBlur;

            // Calculate the difference between the blurred pixels and the original
            // and set the pixels
            for ($x = 0; $x < $w; $x++) { // each row
                for ($y = 0; $y < $h; $y++) { // each pixel
                    $rgbOrig = ImageColorAt($imgCanvas2, $x, $y);
                    $rOrig = (($rgbOrig >> 16) & 0xFF);
                    $gOrig = (($rgbOrig >> 8) & 0xFF);
                    $bOrig = ($rgbOrig & 0xFF);
                    $rgbBlur = ImageColorAt($imgCanvas, $x, $y);
                    $rBlur = (($rgbBlur >> 16) & 0xFF);
                    $gBlur = (($rgbBlur >> 8) & 0xFF);
                    $bBlur = ($rgbBlur & 0xFF);

                    // When the masked pixels differ less from the original
                    // than the threshold specifies, they are set to their original value.
                    $rNew = (abs($rOrig - $rBlur) >= $threshold) ? round(max(0, min(255, ($amount * ($rOrig - $rBlur)) + $rOrig))) : round($rOrig);
                    $gNew = (abs($gOrig - $gBlur) >= $threshold) ? round(max(0, min(255, ($amount * ($gOrig - $gBlur)) + $gOrig))) : round($gOrig);
                    $bNew = (abs($bOrig - $bBlur) >= $threshold) ? round(max(0, min(255, ($amount * ($bOrig - $bBlur)) + $bOrig))) : round($bOrig);

                    if (($rOrig != $rNew) || ($gOrig != $gNew) || ($bOrig != $bNew)) {
                        $pixCol = ImageColorAllocate($img, $rNew, $gNew, $bNew);
                        ImageSetPixel($img, $x, $y, $pixCol);
                    }
                }
            }
            return $img;
        }
    }

    $_CONFIG['types'] = array('', '.gif', '.jpg', '.png');


    // Evaluate and verify passed parameters
    if (empty($params['cache'])) {
        $_CONFIG['cache'] = './cache/';
    } else {
        $_CONFIG['cache'] = $params['cache'];
    }

    # added by Jan Mensik - support url
    if ($params['url']) {
        /*
        $template->_checkPlugins(
            array(
                array(
                    'function' => 'smarty_filemtime_remote',
                    'file'     => SMARTY_PLUGINS_DIR . 'shared.filemtime_remote.php'
                )
            )
        );
        */

        // require_once $smarty->_get_plugin_filepath('shared', 'filemtime_remote');
        //exit (__DIR__ . '\shared.filemtime_remote.php');
        require_once __DIR__ . '\shared.filemtime_remote.php';

        # cache
        if (!$params['url_cache']) {
            $params['url_cache'] = 24 * 60 * 60; /// 24 hour in seconds
        }

        # to have the correct extension
        unset($back);
        if (preg_match('/^.+\.(jpg|gif|png|jpeg)$/i', $params['url'], $back)) {
            $pripona = $back[1];
        } else {
            $pripona = 'url';
        }

        # create filename
        $filename = $_CONFIG['cache'] . 'remote-image.' . md5($params['url']) . '.' . $pripona;

        $fmt_local = filemtime($filename);

        # remote-image exists and url_cache has not expired yet
        if (file_exists($filename) && $fmt_local > time() - $params['url_cache']) {
            $params['file'] = $filename;
        } elseif (!$params['cache_forced'] && file_exists($filename) && $fmt_local >= filemtime_remote($params['url']) && filemtime_remote($params['url']) > 0) {
            # remote-image exists and the remote file is not newer than my copy (unless disabled via cache_forced)
            $params['file'] = $filename;
            touch($params['file']);
        } else {
            # need to download
            $def_no_cache = true;
            # try to load from url
            $file_data = @file($params['url']);
            if (is_array($file_data)) {
                $input = @implode('', $file_data);
            }
            if ($input) {
                # save to cache directory
                $fp = fopen($filename, 'w');
                fwrite($fp, $input);
                fclose($fp);

                # write filename to $params['file']
                $params['file'] = $filename;
            }
        }
        if (!$params['name']) {
            $params['name'] = 'remote-cache.' . md5($params['url'] . implode('', $params));
        }
    }

    # changed by Jan Mensik: no image = no error report
    if (empty($params['file']) or !file_exists($params['file'])) {
        # default image?
        if ($params['default'] && file_exists($params['default'])) {
            $params['file'] = $params['default'];
        } else {
            return;
        }
    }
    //if (empty($params['file'])) { $smarty->_trigger_fatal_error("thumb: parameter 'file' cannot be empty");return; }
    //if (!file_exists($params['file'])) { $smarty->_trigger_fatal_error("thumb: image file does not exist");return; }

    // Get info about source (SRC)
    $temp = getimagesize($params['file']);

    $_SRC['file']       = $params['file'];
    $_SRC['width']      = $temp[0];
    $_SRC['height']     = $temp[1];
    $_SRC['type']       = $temp[2]; // 1=GIF, 2=JPG, 3=PNG, SWF=4
    $_SRC['string']     = $temp[3];
    $_SRC['filename']   = basename($params['file']);
    $_SRC['modified']   = filemtime($params['file']);

    if (empty($params['link'])) {
        $params['link'] = true;
    }
    if (empty($params['window'])) {
        $params['window'] = true;
    }
    if (empty($params['hint'])) {
        $params['hint'] = true;
    }
    if (empty($params['extrapolate'])) {
        $params['extrapolate'] = true;
    }
    if (empty($params['dev'])) {
        $params['crop'] = false;
    }
    if (empty($params['crop'])) {
        $params['crop'] = true;
    }
    if (empty($params['width']) && empty($params['height']) && empty($params['longside']) && empty($params['shortside'])) {
        $params['width'] = $_SRC['width'];
    }
    if (empty($params['overlay_position'])) {
        $params['overlay_position'] = 9;
    }

    if (empty($params['fitin'])) {
        $params['fitin'] = false;
    }

    // Jan Mensik: maximum image size before RAM runs out - 2000 px * 2000 px = 4,000,000
    if ($_SRC['width'] * $_SRC['height'] > 20000000) {
        return '!';
    }

    // Create hash
    $_SRC['hash']       = md5($_SRC['file'] . $_SRC['modified'] . implode('', $params));

    $_DST['offset_w'] = 0;
    $_DST['offset_h'] = 0;


    // Calculate info about destination (DST)
    if (is_numeric($params['width'])) {
        $_DST['width'] = $params['width'];
    } else {
        $_DST['width'] = round($params['height'] / ($_SRC['height'] / $_SRC['width']));
    }

    if (is_numeric($params['height'])) {
        $_DST['height']  = $params['height'];
    } else {
        $_DST['height'] = round($params['width'] / ($_SRC['width'] / $_SRC['height']));
    }

    // The aspect ratio should be maintained regardless of whether the image is in portrait or landscape format.
    if (is_numeric($params['longside'])) {
        if ($_SRC['width'] < $_SRC['height']) {
            $_DST['height'] = $params['longside'];
            $_DST['width']  = round($params['longside'] / ($_SRC['height'] / $_SRC['width']));
        } else {
            $_DST['width']  = $params['longside'];
            $_DST['height'] = round($params['longside'] / ($_SRC['width'] / $_SRC['height']));
        }
    } elseif (is_numeric($params['shortside'])) {
        if ($_SRC['width'] < $_SRC['height']) {
            $_DST['width']  = $params['shortside'];
            $_DST['height'] = round($params['shortside'] / ($_SRC['width'] / $_SRC['height']));
        } else {
            $_DST['height'] = $params['shortside'];
            $_DST['width']  = round($params['shortside'] / ($_SRC['height'] / $_SRC['width']));
        }
    }

    if ($params['fitin'] == 'true') {
        # get aspect ratios
        $width_ratio = $_SRC['width'] / $_DST['width'];
        $height_ratio = $_SRC['height'] / $_DST['height'];

        # logic: take the larger ratio and divide the original dimensions by it
        $width_ratio > $height_ratio ? $ratio = $width_ratio : $ratio = $height_ratio;

        $_DST['width'] = round($_SRC['width'] / $ratio);
        $_DST['height'] = round($_SRC['height'] / $ratio);
    }

    // Should it be cropped? (Standard)
    if ($params['crop'] == 'true') {
        $width_ratio = $_SRC['width'] / $_DST['width'];
        $height_ratio = $_SRC['height'] / $_DST['height'];

        // It must be cropped at the width
        if ($width_ratio > $height_ratio) {
            $_DST['offset_w'] = round(($_SRC['width'] - $_DST['width'] * $height_ratio) / 2);
            $_SRC['width'] = round($_DST['width'] * $height_ratio);
        } elseif ($width_ratio < $height_ratio) {
            // it must be cropped at the height
            $_DST['offset_h'] = round(($_SRC['height'] - $_DST['height'] * $width_ratio) / 2);
            $_SRC['height'] = round($_DST['height'] * $width_ratio);
        }
    }

    // If the source image is smaller than the target image, it should not be upscaled and the newly calculated values are overwritten again
    if ($params['extrapolate'] == 'false' && $_DST['height'] > $_SRC['height'] && $_DST['width'] > $_SRC['width']) {
        $_DST['width'] = $_SRC['width'];
        $_DST['height'] = $_SRC['height'];
    }

    if (!empty($params['type'])) {
        $_DST['type']  = $params['type'];
    } else {
        $_DST['type']  = $_SRC['type'];
    }

    # change by Jan Mensik
    if ($params['name']) {
        $_DST['file']   = $_CONFIG['cache'] . $params['name'] . $_CONFIG['types'][$_DST['type']];
    } else {
        $_DST['file']       = $_CONFIG['cache'] . $_SRC['hash'] . $_CONFIG['types'][$_DST['type']];
    }


    if ($params['nosize'] != "true") {
        $_DST['string'] = 'width="' . $_DST['width'] . '" height="' . $_DST['height'] . '"';
    }

    # change by Jan Mensik
    if ($params['baseimgurl']) {
        $_DST['imgurl'] = addslashes($params['baseimgurl']) . substr($_DST['file'], 1);
    } else {
        $_DST['imgurl']     = $_DST['file'];
    }

    // Is there a frame?
    if (!empty($params['frame'])) {
        // check if valid
        $imagesize = getimagesize($params['frame']);
        if ($imagesize[0] != $imagesize[1] or $imagesize[0] % 3 or !file_exists($params['frame'])) {
            # $template->_trigger_fatal_error("thumb: wrong dimensions of 'frame'-image or width and height is not a multiplier of 3");
            return;
        }
        // Block size needed here if a cached image is to be played back
        $frame_blocksize = $imagesize[0] / 3;

        $_DST['string'] = 'width="' . ($_DST['width'] + 2 * $frame_blocksize) . '" height="' . ($_DST['height'] + 2 * $frame_blocksize) . '"';
    }

    // Create return strings
    // change by Jan Mensik (added 'justimg')
    if ($params['justimg']) {
        $_RETURN['img'] = $_DST['imgurl'];
    } elseif (empty($params['html'])) {
        $_RETURN['img'] = '<img src="' . $_DST['imgurl'] . '" ' . $params['html'] . ' ' . ($params['noimgsize'] ? '' : $_DST['string']) . ' alt="" title="" />';
    } else {
        $_RETURN['img'] = '<img src="' . $_DST['imgurl'] . '" ' . $params['html'] . ' ' . ($params['noimgsize'] ? '' : $_DST['string']) . ' />';
    }

    if ($params['link'] == "true") {
        if (empty($params['linkurl'])) {
            unset($temp);
            if ($params['baseimgurl'] && preg_match('/^\.\/(.*)$/', $_SRC['file'], $temp)) {
                $params['linkurl'] = $params['baseimgurl'] . substr($_SRC['file'], 1);
            } else {
                $params['linkurl'] = $_SRC['file'];
            }
        }

        # change by Jan Mensik (added 'linkhtml')
        if ($params['window'] == "true") {
            $returner = '<a href="' . $params['linkurl'] . '" target="_blank" ' . $params['linkhtml'] . '>' . $_RETURN['img'] . '</a>';
        } else {
            $returner = '<a href="' . $params['linkurl'] . '" ' . $params['linkhtml'] . '>' . $_RETURN['img'] . '</a>';
        }
    } else {
        $returner = $_RETURN['img'];
    }

    // Catch cache file
    if (file_exists($_DST['file']) and !$params['dev'] and !$def_no_cache) {
        return $returner;
    }


    // Otherwise continue

    // Read SRC
    if ($_SRC['type'] == 1) {
        $_SRC['image'] = imagecreatefromgif($_SRC['file']);
    }
    if ($_SRC['type'] == 2) {
        $_SRC['image'] = imagecreatefromjpeg($_SRC['file']);
    }
    if ($_SRC['type'] == 3) {
        $_SRC['image'] = imagecreatefrompng($_SRC['file']);
    }

    // If image is very large, first downscale linearly to four times target size and overwrite $_SRC
    if ($_DST['width'] * 4 < $_SRC['width'] and $_DST['height'] * 4 < $_SRC['height']) {
        // Multiplier of target size
        $_TMP['width'] = round($_DST['width'] * 4);
        $_TMP['height'] = round($_DST['height'] * 4);

        $_TMP['image'] = imagecreatetruecolor($_TMP['width'], $_TMP['height']);
        imagecopyresized($_TMP['image'], $_SRC['image'], 0, 0, $_DST['offset_w'], $_DST['offset_h'], $_TMP['width'], $_TMP['height'], $_SRC['width'], $_SRC['height']);
        $_SRC['image'] = $_TMP['image'];
        $_SRC['width'] = $_TMP['width'];
        $_SRC['height'] = $_TMP['height'];

        // If pre-scaled, no other area may be cut out
        $_DST['offset_w'] = 0;
        $_DST['offset_h'] = 0;
        unset($_TMP['image']);
    }

    // Create DST
    $_DST['image'] = imagecreatetruecolor($_DST['width'], $_DST['height']);
    imagecopyresampled($_DST['image'], $_SRC['image'], 0, 0, $_DST['offset_w'], $_DST['offset_h'], $_DST['width'], $_DST['height'], $_SRC['width'], $_SRC['height']);
    if ($params['sharpen'] != "false") {
        $_DST['image'] = UnsharpMask($_DST['image'], 80, .5, 3);
    }

    // Should a magnifier be inserted?
    if ($params['hint'] == "true") {
        // Should the white bar really be added?
        if ($params['addgreytohint'] != 'false') {
            $trans = imagecolorallocatealpha($_DST['image'], 255, 255, 255, 25);
            imagefilledrectangle($_DST['image'], 0, $_DST['height'] - 9, $_DST['width'], $_DST['height'], $trans);
        }

        $magnifier = imagecreatefromstring(gzuncompress(base64_decode("eJzrDPBz5+WS4mJgYOD19HAJAtLcIMzBBiRXrilXA1IsxU6eIRxAUMOR0gHkcxZ4RBYD1QiBMOOlu3V/gIISJa4RJc5FqYklmfl5CiGZuakMBoZ6hkZ6RgYGJs77ex2BalRBaoLz00rKE4tSGXwTk4vyc1NTMhMV3DKLUsvzi7KLFXwjFEAa2svWnGdgYPTydHEMqZhTOsE++1CAyNHzm2NZjgau+dAmXlAwoatQmOld3t/NPxlLMvY7sovPzXHf7re05BPzjpQTMkZTPjm1HlHkv6clYWK43Zt16rcDjdZ/3j2cd7qD4/HHH3GaprFrw0QZDHicORXl2JsPsveVTDz//L3N+WpxJ5Hff+10Tjdd2/Vi17vea79Om5w9zzyne9GLnWGrN8atby/ayXPOsu2w4quvVtxNCVVz5nAf3nDpZckBCedpqSc28WTOWnT7rZNXZSlPvFybie9EFc6y3bIMCn3JAoJ+kyyfn9qWq+LZ9Las26Jv482cDRE6Ci0B6gVbo2oj9KabzD8vyMK4ZMqMs2kSvW4chz88SXNzmeGjtj1QZK9M3HHL8L7HITX3t19//VVY8CYDg9Kvy2vDXu+6mGGxNOiltMPsjn/t9eJr0ja/FOdi5TyQ9Lz3fOqstOr99/dnro2vZ1jy76D/vYivPsBoYPB09XNZ55TQBAAJjs5s</body>")));
        imagealphablending($_DST['image'], true);
        imagecopy($_DST['image'], $magnifier, $_DST['width'] - 15, $_DST['height'] - 14, 0, 0, 11, 11);
    }

    // Should an overlay image be added?
    if (!empty($params['overlay'])) {
        // load "overlay" image
        $overlay = imagecreatefrompng($params['overlay']);
        $overlay_size = getimagesize($params['overlay']);

        // Copy overlay image to correct position
        if ($params['overlay_position'] == '1') {
            imagecopy($_DST['image'], $overlay, 0, 0, 0, 0, $overlay_size[0], $overlay_size[1]); // top left corner
        }
        if ($params['overlay_position'] == '2') {
            imagecopy($_DST['image'], $overlay, $_DST['width'] / 2 - $overlay_size[0] / 2, 0, 0, 0, $overlay_size[0], $overlay_size[1]); // top middle
        }
        if ($params['overlay_position'] == '3') {
            imagecopy($_DST['image'], $overlay, $_DST['width'] - $overlay_size[0], 0, 0, 0, $overlay_size[0], $overlay_size[1]); // top right corner
        }
        if ($params['overlay_position'] == '4') {
            imagecopy($_DST['image'], $overlay, 0, $_DST['height'] / 2 - $overlay_size[1] / 2, 0, 0, $overlay_size[0], $overlay_size[1]); // middle left
        }
        if ($params['overlay_position'] == '5') {
            imagecopy($_DST['image'], $overlay, $_DST['width'] / 2 - $overlay_size[0] / 2, $_DST['height'] / 2 - $overlay_size[1] / 2, 0, 0, $overlay_size[0], $overlay_size[1]); // center
        }
        if ($params['overlay_position'] == '6') {
            imagecopy($_DST['image'], $overlay, $_DST['width'] - $overlay_size[0], $_DST['height'] / 2 - $overlay_size[1] / 2, 0, 0, $overlay_size[0], $overlay_size[1]); // middle right
        }
        if ($params['overlay_position'] == '7') {
            imagecopy($_DST['image'], $overlay, 0, $_DST['height'] - $overlay_size[1], 0, 0, $overlay_size[0], $overlay_size[1]); // bottom left corner
        }
        if ($params['overlay_position'] == '8') {
            imagecopy($_DST['image'], $overlay, $_DST['width'] / 2 - $overlay_size[0] / 2, $_DST['height'] - $overlay_size[1], 0, 0, $overlay_size[0], $overlay_size[1]); // bottom middle
        }
        if ($params['overlay_position'] == '9') {
            imagecopy($_DST['image'], $overlay, $_DST['width'] - $overlay_size[0], $_DST['height'] - $overlay_size[1], 0, 0, $overlay_size[0], $overlay_size[1]); // bottom right corner
        }
    }

    // Add calculation time
    if ($params['dev']) {
        // Stop time
        $time['end'] = getmicrotime();
        $time = round($time['end'] - $time['start'], 2);

        // Define colors
        $white_trans = imagecolorallocatealpha($_DST['image'], 255, 255, 255, 25);
        $black = ImageColorAllocate($_DST['image'], 0, 0, 0);

        // White bar at the top
        imagefilledrectangle($_DST['image'], 0, 0, $_DST['width'], 10, $white_trans);

        // Text with time indication
        imagestring($_DST['image'], 1, 5, 2, 'time: ' . $time . 's', $black);
    }

    // Should a frame be added?
    if (!empty($params['frame'])) {
        // load and initialize "frame" image
        $frame = imagecreatefrompng($params['frame']);
        $frame_blocksize = $imagesize[0] / 3;

        // Create new image and copy the image generated so far into it
        $_FRAME['image'] = imagecreatetruecolor($_DST['width'] + 2 * $frame_blocksize, $_DST['height'] + 2 * $frame_blocksize);
        imagecopy($_FRAME['image'], $_DST['image'], $frame_blocksize, $frame_blocksize, 0, 0, $_DST['width'], $_DST['height']);

        // Now draw the other frames around it
        // the corners
        imagecopy($_FRAME['image'], $frame, 0, 0, 0, 0, $frame_blocksize, $frame_blocksize); // top left corner
        imagecopy($_FRAME['image'], $frame, $_DST['width'] + $frame_blocksize, 0, 2 * $frame_blocksize, 0, $frame_blocksize, $frame_blocksize); // top right corner
        imagecopy($_FRAME['image'], $frame, $_DST['width'] + $frame_blocksize, $_DST['height'] + $frame_blocksize, 2 * $frame_blocksize, 2 * $frame_blocksize, $frame_blocksize, $frame_blocksize); // bottom right corner
        imagecopy($_FRAME['image'], $frame, 0, $_DST['height'] + $frame_blocksize, 0, 2 * $frame_blocksize, $frame_blocksize, $frame_blocksize); // bottom left corner
        // now the sides
        imagecopyresized($_FRAME['image'], $frame, $frame_blocksize, 0, $frame_blocksize, 0, $_DST['width'], $frame_blocksize, $frame_blocksize, $frame_blocksize); // top
        imagecopyresized($_FRAME['image'], $frame, $_DST['width'] + $frame_blocksize, $frame_blocksize, 2 * $frame_blocksize, $frame_blocksize, $frame_blocksize, $_DST['height'], $frame_blocksize, $frame_blocksize); // right
        imagecopyresized($_FRAME['image'], $frame, $frame_blocksize, $_DST['height'] + $frame_blocksize, $frame_blocksize, 2 * $frame_blocksize, $_DST['width'], $frame_blocksize, $frame_blocksize, $frame_blocksize); // bottom
        imagecopyresized($_FRAME['image'], $frame, 0, $frame_blocksize, 0, $frame_blocksize, $frame_blocksize, $_DST['height'], $frame_blocksize, $frame_blocksize); // left

        $_DST['image']  = $_FRAME['image'];
        $_DST['width']  = $_DST['width'] + 2 * $frame_blocksize;
        $_DST['height'] = $_DST['height'] + 2 * $frame_blocksize;
        $_DST['string2']    = 'width="' . $_DST['width'] . '" height="' . $_DST['height'] . '"';

        $returner = str_replace($_DST['string'], $_DST['string2'], $returner);
    }

    // Save thumbnail
    if ($_DST['type'] == 1) {
        imagetruecolortopalette($_DST['image'], false, 256);
        imagegif($_DST['image'], $_DST['file']);
    }
    if ($_DST['type'] == 2) {
        Imageinterlace($_DST['image'], 1);
        if (empty($params['quality'])) {
            $params['quality'] = 85;
        }
        imagejpeg($_DST['image'], $_DST['file'], $params['quality']);
    }
    if ($_DST['type'] == 3) {
        imagepng($_DST['image'], $_DST['file']);
    }

    // Output image
    return $returner;
}
