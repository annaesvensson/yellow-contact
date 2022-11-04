<?php
// Contact extension, https://github.com/annaesvensson/yellow-contact

class YellowContact {
    const VERSION = "0.8.21";
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
    
    // Handle page content of shortcut
    public function onParseContentShortcut($page, $name, $text, $type) {
        $output = null;
        if ($name=="contact" && ($type=="block" || $type=="inline")) {
            list($location) = $this->yellow->toolbox->getTextArguments($text);
            if (is_string_empty($location)) $location = $this->yellow->system->get("contactLocation");
            $output = "<div class=\"".htmlspecialchars($name)."\">\n";
            $output .= "<form class=\"contact-form\" action=\"".$page->base.$location."\" method=\"post\">\n";
            $output .= "<p class=\"contact-name\"><label for=\"name\">".$this->yellow->language->getTextHtml("contactName")."</label><br /><input type=\"text\" class=\"form-control\" name=\"name\" id=\"name\" value=\"\" /></p>\n";
            $output .= "<p class=\"contact-email\"><label for=\"email\">".$this->yellow->language->getTextHtml("contactEmail")."</label><br /><input type=\"text\" class=\"form-control\" name=\"email\" id=\"email\" value=\"\" /></p>\n";
            $output .= "<p class=\"contact-message\"><label for=\"message\">".$this->yellow->language->getTextHtml("contactMessage")."</label><br /><textarea class=\"form-control\" name=\"message\" id=\"message\" rows=\"7\" cols=\"70\"></textarea></p>\n";
            $output .= "<p class=\"contact-consent\"><input type=\"checkbox\" name=\"consent\" value=\"consent\" id=\"consent\"> <label for=\"consent\">".$this->yellow->language->getTextHtml("contactConsent")."</label></p>\n";
            $output .= "<input type=\"hidden\" name=\"referer\" value=\"".$page->getUrl()."\" />\n";
            $output .= "<input type=\"hidden\" name=\"status\" value=\"send\" />\n";
            $output .= "<input type=\"submit\" value=\"".$this->yellow->language->getTextHtml("contactButton")."\" class=\"btn contact-btn\" />\n";
            $output .= "</form>\n";
            $output .= "</div>\n";
        }
        return $output;
    }
    
    // Handle page layout
    public function onParsePageLayout($page, $name) {
        if ($name=="contact") {
            if ($this->yellow->isCommandLine()) $page->error(500, "Static website not supported!");
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
        $spamFilter = $this->yellow->system->get("contactSpamFilter");
        $sitename = $this->yellow->system->get("sitename");
        $siteEmail = $this->yellow->system->get("contactSiteEmail");
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
        if (!is_string_empty($senderEmail) && !filter_var($senderEmail, FILTER_VALIDATE_EMAIL)) $status = "invalid";
        if (is_string_empty($userEmail) || !filter_var($userEmail, FILTER_VALIDATE_EMAIL)) $status = "settings";
        if ($status=="send") {
            $mailTo = mb_encode_mimeheader("$userName")." <$userEmail>";
            $mailSubject = mb_encode_mimeheader($this->yellow->page->get("title"));
            $mailHeaders = mb_encode_mimeheader("From: $sitename")." <$siteEmail>\r\n";
            $mailHeaders .= mb_encode_mimeheader("Reply-To: $senderName")." <$senderEmail>\r\n";
            $mailHeaders .= mb_encode_mimeheader("X-Referer-Url: ".$referer)."\r\n";
            $mailHeaders .= mb_encode_mimeheader("X-Request-Url: ".$this->yellow->page->getUrl())."\r\n";
            if ($spamFilter!="none" && preg_match("/$spamFilter/i", $message)) {
                $mailSubject = mb_encode_mimeheader($this->yellow->language->getText("contactMailSpam")." ".$this->yellow->page->get("title"));
                $mailHeaders .= "X-Spam-Flag: YES\r\n";
                $mailHeaders .= "X-Spam-Status: Yes, score=1\r\n";
            }
            $mailHeaders .= "Mime-Version: 1.0\r\n";
            $mailHeaders .= "Content-Type: text/plain; charset=utf-8\r\n";
            $mailMessage = "$header\r\n\r\n$message\r\n-- \r\n$footer";
            $status = mail($mailTo, $mailSubject, $mailMessage, $mailHeaders) ? "done" : "error";
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
}
