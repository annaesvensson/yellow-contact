<?php
// Contact extension, https://github.com/annaesvensson/yellow-contact

class YellowContact {
    const VERSION = "0.9.2"; // ACS 
    public $yellow;         // access to API
    
    // Handle initialisation
    public function onLoad($yellow) {
        $this->yellow = $yellow;
        $this->yellow->system->setDefault("contactSiteEmail", "noreply");
        $this->yellow->system->setDefault("contactLocation", "/contact/");
        $this->yellow->system->setDefault("contactEmailRestriction", "0");
        $this->yellow->system->setDefault("contactLinkRestriction", "0");
        $this->yellow->system->setDefault("contactSpamFilter", "advert|promot|market|click here");
    }
    
    // Handle page content element
    public function onParseContentElement($page, $name, $text, $attributes, $type) {
        $output = null;
        if ($name=="contact" && ($type=="block" || $type=="inline")) {
            $this->onParsePageLayout($page, $name); # ACS - added as we need this status informations also for the dynamic page
            list($location) = $this->yellow->toolbox->getTextArguments($text);
            if (is_string_empty($location)) $location = $page->location; // ACS - to ensue the local dynamic form get used in case of submit action
            if (is_string_empty($location)) $location = $this->yellow->system->get("contactLocation");
            
           if ($this->yellow->page->get("status")!="done") { // ACS this if else structure is also stolen from original contact.html
                $captcha = $this->getRandomCaptchaString(); // ACS Create an random captcha sting
                $output = "<div class=\"".htmlspecialchars($name)."\">\n";
                $output .= "<p class=\"".$this->yellow->page->getHtml("status")."\">".$this->yellow->language->getTextHtml("contactStatus".ucfirst($this->yellow->page->get("status")))."</p>\n"; // ACS - copied in from original contact.html to have a status line
                $output .= "<form class=\"contact-form\" action=\"".$page->base.$location."\" method=\"post\">\n";
                $output .= "<p class=\"contact-name\"><label for=\"name\">".$this->yellow->language->getTextHtml("contactName")."</label><br /><input type=\"text\" class=\"form-control\" name=\"name\" id=\"name\" value=\"".$this->yellow->page->getRequestHtml("name")."\" /></p>\n";
                $output .= "<p class=\"contact-email\"><label for=\"email\">".$this->yellow->language->getTextHtml("contactEmail")."</label><br /><input type=\"text\" class=\"form-control\" name=\"email\" id=\"email\" value=\"".$this->yellow->page->getRequestHtml("email")."\" /></p>\n";
                $output .= "<p class=\"contact-message\"><label for=\"message\">".$this->yellow->language->getTextHtml("contactMessage")."</label><br /><textarea class=\"form-control\" name=\"message\" id=\"message\" rows=\"7\" cols=\"70\">".$this->yellow->page->getRequestHtml("message")."</textarea></p>\n";
                $output .= "<p class=\"contact-consent\"><input type=\"checkbox\" name=\"consent\" value=\"consent\" id=\"consent\"".($this->yellow->page->isRequest("consent") ? " checked=\"checked\"" : "")."> <label for=\"consent\">".$this->yellow->language->getTextHtml("contactConsent")."</label></p>\n";
            
                $output .= "<p class=\"contact-captcha\">".$this->getCaptcha($captcha)."<br /><input type=\"tel\" class=\"form-control\" name=\"captcha\" id=\"captcha\" placeholder=\"enter captcha\" pattern=\"[0-9]{6}\" maxlength=\"6\"  /></p>\n"; // ACS - "tel" is a super input type for this - TODO: having a languge specific test string
                $output .= "<input type=\"hidden\" name=\"captcha_hash\" value=\"".$this->createCaptchaHash($captcha)."\" />\n";
                
                $output .= "<input type=\"hidden\" name=\"referer\" value=\"".$page->getUrl()."\" />\n";
                $output .= "<input type=\"hidden\" name=\"status\" value=\"send\" />\n";
                $output .= "<input type=\"submit\" value=\"".$this->yellow->language->getTextHtml("contactButton")."\" class=\"btn contact-btn\" />\n";
                $output .= "</form>\n";
                $output .= "</div>\n";
            } else {
                $output =  "<p>".$this->yellow->language->getTextHtml("contactStatusDone")."<p>";
            }
        }
        return $output;
    }
    
    // Handle page layout
    public function onParsePageLayout($page, $name) {
        if ($name=="contact") {
            if ($this->yellow->lookup->isCommandLine()) $page->error(500, "Static website not supported!");
            if (!$page->isRequest("referer")) {
                $page->setRequest("referer", $this->yellow->toolbox->getServer("HTTP_REFERER"));
                $page->setHeader("Last-Modified", $this->yellow->toolbox->getHttpDateFormatted(time()));
                $page->setHeader("Cache-Control", "no-cache, no-store");
            }
            if ($page->getRequest("status")=="send") {
                $status = $this->sendMail();
                if ($status=="settings") $page->error(500, "Contact page settings not valid!");
                if ($status=="error") $page->error(500, $this->yellow->language->getText("contactStatusError"));
                $page->setHeader("Last-Modified", $this->yellow->toolbox->getHttpDateFormatted(time()));
                $page->setHeader("Cache-Control", "no-cache, no-store");
                $page->set("status", $status);
            } else {
                $page->set("status", "none");
            }
        }
    }
    
    // Send contact email
    public function sendMail() {
        $status = "send";
        $senderName = trim(preg_replace("/[^\pL\d\-\. ]/u", "-", $this->yellow->page->getRequest("name")));
        $senderEmail = trim($this->yellow->page->getRequest("email"));
        $message = trim($this->yellow->page->getRequest("message"));
        $consent = trim($this->yellow->page->getRequest("consent"));
        $referer = trim($this->yellow->page->getRequest("referer"));
        $captcha = trim($this->yellow->page->getRequest("captcha")); // ACS
        $captchaHash = trim($this->yellow->page->getRequest("captcha_hash")); // ACS
        $spamFilter = $this->yellow->system->get("contactSpamFilter");
        $sitename = $this->yellow->system->get("sitename");
        $siteEmail = $this->yellow->system->get("contactSiteEmail");
        $subject = $this->yellow->page->get("title");
        $header = $this->getMailHeader($senderName, $senderEmail);
        $footer = $this->getMailFooter($referer);
        $userName = $this->yellow->system->get("author");
        $userEmail = $this->yellow->system->get("email");

        if ($this->yellow->page->isExisting("author") && !$this->yellow->system->get("contactEmailRestriction")) {
            $userName = $this->yellow->page->get("author");
        }
        if ($this->yellow->page->isExisting("email") && !$this->yellow->system->get("contactEmailRestriction")) {
            $userEmail = $this->yellow->page->get("email");
        }
        if ($this->yellow->system->get("contactLinkRestriction") && $this->checkClickable($message)) $status = "review";
        if (is_string_empty($senderName) || is_string_empty($senderEmail) ||
            is_string_empty($message) || is_string_empty($consent)) $status = "incomplete";
        if (is_string_empty($captcha) ) $status = "incomplete"; // ACS TODO: Make anew status for this missing captcha
        if (!is_string_empty($senderEmail) && !filter_var($senderEmail, FILTER_VALIDATE_EMAIL)) $status = "invalid";
        if (is_string_empty($userEmail) || !filter_var($userEmail, FILTER_VALIDATE_EMAIL)) $status = "settings";
        if ($status=="send") {
            $mailHeaders = array(
                "To" => $this->yellow->lookup->normaliseAddress("$userName <$userEmail>"),
                "From" => $this->yellow->lookup->normaliseAddress("$sitename <$siteEmail>"),
                "Reply-To" => $this->yellow->lookup->normaliseAddress("$senderName <$senderEmail>"),
                "Subject" => $subject,
                "Date" => date(DATE_RFC2822),
                "Mime-Version" => "1.0",
                "Content-Type" => "text/plain; charset=utf-8",
                "X-Referer-Url" => $referer,
                "X-Request-Url" => $this->yellow->page->getUrl());
            if ($spamFilter!="none" && preg_match("/$spamFilter/i", $message)) {
                $mailHeaders["Subject"] = $this->yellow->language->getText("contactMailSpam")." ".$subject;
                $mailHeaders["X-Spam-Flag"] = "YES";
                $mailHeaders["X-Spam-Status"] = "Yes, score=1";
            }
            $mailMessage = "$header\r\n\r\n$message\r\n-- \r\n$footer";
            $status = $this->yellow->toolbox->mail("contact", $mailHeaders, $mailMessage) ? "done" : "error";
        }
        return $status;
    }

    // Return email header
    public function getMailHeader($senderName, $senderEmail) {
        $header = $this->yellow->language->getText("contactMailHeader");
        $header = str_replace("\\n", "\r\n", $header);
        $header = preg_replace("/@sender/i", "$senderName <$senderEmail>", $header);
        $header = preg_replace("/@sendershort/i", strtok($senderName, " "), $header);
        return $header;
    }
    
    // Return email footer
    public function getMailFooter($url) {
        $footer = $this->yellow->language->getText("contactMailFooter");
        $footer = str_replace("\\n", "\r\n", $footer);
        $footer = preg_replace("/@sitename/i", $this->yellow->system->get("sitename"), $footer);
        $footer = preg_replace("/@title/i", $this->findTitle($url, $this->yellow->page->get("title")), $footer);
        return $footer;
    }
    
    // Return title for local page
    public function findTitle($url, $titleDefault) {
        $titleFound = $titleDefault;
        $serverUrl = $this->yellow->lookup->normaliseUrl(
            $this->yellow->system->get("coreServerScheme"),
            $this->yellow->system->get("coreServerAddress"),
            $this->yellow->system->get("coreServerBase"), "");
        $serverUrlLength = strlenu($serverUrl);
        if (substru($url, 0, $serverUrlLength)==$serverUrl) {
            $page = $this->yellow->content->find(substru($url, $serverUrlLength));
            if ($page) $titleFound = $page->get("title");
        }
        return $titleFound;
    }

    // Check if text contains clickable links
    public function checkClickable($text) {
        $found = false;
        foreach (preg_split("/\s+/", $text) as $token) {
            if (preg_match("/([\w\-\.]{2,}\.[\w]{2,})/", $token)) $found = true;
            if (preg_match("/^\w+:\/\//", $token)) $found = true;
        }
        return $found;
    }


    // Create captcha string - ASC
    public function getRandomCaptchaString($length = 6) {
        $stringSpace = '0123456789';
        $stringLength = strlen($stringSpace);
        $randomString = '';
        for ($i = 0; $i < $length; $i ++) {
            $randomString = $randomString . $stringSpace[rand(0, $stringLength - 1)];
        }
        return $randomString;
    }

    // Create captcha hash - ASC
    public function createCaptchaHash($string) {
        $hash = $this->yellow->toolbox->createHash($string, "sha256");
        if (is_string_empty($hash)) $hash = "padd"."error-hash-algorithm-sha256";
        return $hash;
    }
       
    // Create captcha image - ASC
    public function getCaptcha($string) {

        // Begin output buffering
        ob_start();

        // generate the captcha image in some magic way
        $w = 80; $h = 30;
        $image = imagecreate($w, $h);
        $background = imagecolorallocatealpha($image, 127, 127, 127, 63);
        imagefill($image, 0, 0, $background);

        $color[0] = imagecolorallocate($image, 0, 0, 0);    
        $color[1] = imagecolorallocate($image, 255, 255, 255);

        $strlen = strlen($string);
        for( $i = 0; $i < $strlen; $i++ ) {
            $char = substr( $string, $i, 1 );
            $s = rand(0, 9);
            $x = $i * 10;
            $y = rand(0, 9);
            $c = rand(0, 1);

            imagechar($image, 4, $x + 12, $y + 3, $char, $color[$c]);
            if ($y <= 3) imagechar($image, 4, $x + 12, $y + 6, "_", $color[(1-$c)]);
            if ($y >= 6) imagechar($image, 4, $x + 12, $y - 12, "_", $color[(1-$c)]);
        }  
        imagepng($image);

        // and finally retrieve the byte stream
        $rawImageBytes = ob_get_clean();

        imageDestroy($image);

        return '<img src="data:image/png;base64,'. base64_encode( $rawImageBytes ) . '">';

    }

    // Check captcha - ASC
    public function checkCaptcha($string, $hash) {
        return $this->yellow->toolbox->verifyHash($string, "sha256", $hash);
    }
    
}
