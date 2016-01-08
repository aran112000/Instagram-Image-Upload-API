<?php
/**
 * Class instagram_post
 */
class instagram_post {

    const USERNAME = null; // TODO; Enter your Instagram username here to authenticate
    const PASSWORD = null; // TODO; Enter your Instagram password here to authenticate

    const API_ENDPOINT = 'https://instagram.com/api';
    const API_VERSION = 1;

    protected $cookie_file = 'instagram_cookie.txt'; // Path from the root directory

    private $guid = null;
    private $device_id = null;
    private $user_agent = null;

    /**
     * instagram constructor.
     */
    public function __construct() {
        if (self::USERNAME === null) {
            throw new InvalidArgumentException('Please ensure you enter a valid username in instagram::USERNAME');
        }
        if (self::PASSWORD === null) {
            throw new InvalidArgumentException('Please ensure you enter a valid password in instagram::PASSWORD');
        }
    }

    /**
     * @param $filename
     * @param $caption
     *
     * @return bool
     * @throws \Exception
     */
    public function doPostImage($filename, $caption) {
        $filename = $_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . trim(str_replace($_SERVER['DOCUMENT_ROOT'], '', $filename), DIRECTORY_SEPARATOR);
        if (!is_readable($filename)) {
            throw new Exception('Please supply a valid, readable image');
        }
        if ($this->getFileExtension($filename) !== 'jpg') {
            throw new Exception('Images must be in .jpg format');
        }
        if (!$this->isImageSquare($filename)) {
            throw new Exception('Images posted to Instagram MUST be square');
        }

        if ($this->doLogin()) {
            if ($post = $this->doApiCall('media/upload', [
                'device_timestamp' => time(),
                'photo' => '@' . $filename
            ])) {
                if ($post['status'] !== 'ok') {
                    throw new Exception('Invalid response received when trying to upload the image: ' . $post['status']);
                }

                // Now, configure the photo
                if ($upload = $this->doApiCall('media/configure', [
                    'device_id' => $this->getDeviceId(),
                    'guid' => $this->getGuid(),
                    'media_id' => $post['media_id'],
                    'caption' => trim(preg_replace("/\r|\n/", "", $caption)), // Remove and line breaks from the caption
                    'device_timestamp' => time(),
                    'source_type' => 5,
                    'filter_type' => 0,
                    'Content-Type' => 'application/x-www-form-urlencoded; charset=UTF-8'
                ])) {
                    $status = $upload['status'];

                    return ($status !== 'fail');
                }
            }
        }

        return false;
    }

    /**
     * @return mixed
     * @throws \Exception
     */
    private function doLogin() {
        $data = [
            'device_id' => $this->getDeviceId(),
            'guid' => $this->getGuid(),
            'username' => self::USERNAME,
            'password' => self::PASSWORD,
            'Content-Type' => 'application/x-www-form-urlencoded; charset=UTF-8'
        ];

        return $this->doApiCall('accounts/login', $data, false);
    }

    /**
     * @param       $endpoint
     * @param array $post_data
     * @param bool  $cookies
     *
     * @return mixed
     * @throws \Exception
     */
    private function doApiCall($endpoint, $post_data, $cookies = true) {
        // Add signing fields
        $post_data = array_merge([
            'signed_body' => $this->getRequestSignature($post_data) . '.' . json_encode($post_data),
            'ig_sig_key_version' => 4,
        ], $post_data);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, self::API_ENDPOINT . '/v' . self::API_VERSION . '/' . trim($endpoint, '/') . '/');
        curl_setopt($ch, CURLOPT_USERAGENT, $this->getUserAgentString());
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

        if (is_array($post_data)) {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
        }

        if ($cookies) {
            curl_setopt($ch, CURLOPT_COOKIEFILE, $this->getCookiePath());
        } else {
            curl_setopt($ch, CURLOPT_COOKIEJAR, $this->getCookiePath());
        }

        $response = curl_exec($ch);
        curl_close($ch);

        if ($json_response = json_decode($response, true)) {
            return $json_response;
        }

        throw new Exception('API Request failed: ' . $response);
    }

    /**
     * @return string
     */
    private function getCookiePath() {
        return $_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . trim($this->cookie_file, DIRECTORY_SEPARATOR);
    }

    /**
     * @param $filename
     *
     * @return string
     */
    private function getFileExtension($filename) {
        $filename_parts = explode('.', basename($filename));
        return strtolower(end($filename_parts));
    }

    /**
     * @param $filename
     *
     * @return bool
     */
    private function isImageSquare($filename) {
        list($width, $height) = getimagesize($filename);

        return ($width === $height);
    }

    /**
     * @return null|string
     */
    private function getDeviceId() {
        if ($this->device_id === null) {
            $this->device_id = "android-" . $this->getGuid();
        }

        return $this->device_id;
    }

    /**
     * @return null|string
     */
    private function getGuid() {
        if ($this->guid === null) {
            $this->guid = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x', mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(16384, 20479), mt_rand(32768, 49151), mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(0, 65535));
        }

        return $this->guid;
    }

    /**
     * @param array $data
     *
     * @return string
     */
    private function getRequestSignature(array $data) {
        return hash_hmac('sha256', json_encode($data), 'b4a23f5e39b5929e0666ac5de94c89d1618a2916');
    }

    /**
     * @return null|string
     */
    private function getUserAgentString() {
        if ($this->user_agent === null) {
            $resolutions = ['720x1280', '320x480', '480x800', '1024x768', '1280x720', '768x1024', '480x320'];
            $versions = ['GT-N7000', 'SM-N9000', 'GT-I9220', 'GT-I9100'];
            $dpis = ['120', '160', '320', '240'];

            $ver = $versions[array_rand($versions)];
            $dpi = $dpis[array_rand($dpis)];
            $res = $resolutions[array_rand($resolutions)];

            $this->user_agent = 'Instagram 4.' . mt_rand(1, 2) . '.' . mt_rand(0, 2) . ' Android (' . mt_rand(10, 11) . '/' . mt_rand(1, 3) . '.' . mt_rand(3, 5) . '.' . mt_rand(0, 5) . '; ' . $dpi . '; ' . $res . '; samsung; ' . $ver . '; ' . $ver . '; smdkc210; en_US)';
        }

        return $this->user_agent;
    }
}
