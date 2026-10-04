<?php
// Contact extension, https://github.com/annaesvensson/yellow-contact

class YellowContact {
    const VERSION = "1.0.2";
    public $yellow;         // access to API
    
    // Handle initialisation
    public function onLoad($yellow) {
        $this->yellow = $yellow;
        $this->yellow->system->setDefault("contactEmailRestriction", "0");
        $this->yellow->system->setDefault("contactLinkRestriction", "0");
        $this->yellow->system->setDefault("contactSpamFilter", "advert|promot|market|click here");
    }
    
    // Handle page layout
    public function onParsePageLayout($page, $name) {
        if ($name=="contact") {
            if ($this->yellow->lookup->isCommandLine()) $page->error(500, "Can't generate static page!");
            if (!$page->isRequest("referer")) {
                $page->setRequest("referer", $this->yellow->toolbox->getServer("HTTP_REFERER"));
                $page->setHeader("Last-Modified", $this->yellow->toolbox->getHttpDateFormatted(time()));
                $page->setHeader("Cache-Control", "no-cache, no-store");
            }
            if ($page->getRequest("status")=="send") {
                list($status, $data) = $this->validateInputData($page);
                if ($status=="send") $status = $this->sendMail($data);
                if ($status=="error") $page->error(500, "Can't send email message!");
                $page->setHeader("Last-Modified", $this->yellow->toolbox->getHttpDateFormatted(time()));
                $page->setHeader("Cache-Control", "no-cache, no-store");
                $page->set("status", $status);
            } else {
                $page->set("status", "none");
            }
        }
    }
    
    // Validate input data
    public function validateInputData($page) {
        $status = "send";
        $data = array(
            "senderName" => trim(preg_replace("/[^\pL\d\-\. ]/u", "-", $page->getRequest("name"))),
            "senderEmail" => trim($page->getRequest("email")),
            "message" => trim($page->getRequest("message")),
            "consent" => trim($page->getRequest("consent")),
            "referer" => trim($page->getRequest("referer")),
            "subject" => $page->get("title"),
            "userName" => $this->yellow->system->get("author"),
            "userEmail" => $this->yellow->system->get("email"),
            "spam" => false);
        if ($page->isExisting("author") && !$this->yellow->system->get("contactEmailRestriction")) {
            $data["userName"] = $page->get("author");
        }
        if ($page->isExisting("email") && !$this->yellow->system->get("contactEmailRestriction")) {
            $data["userEmail"] = $page->get("email");
        }
        if ($this->yellow->system->get("contactSpamFilter")!="none") {
            $regex = "/".$this->yellow->system->get("contactSpamFilter")."/i";
            $data["spam"] = preg_match($regex, $data["message"]);
        }
        if ($this->yellow->system->get("contactLinkRestriction") && $this->checkClickable($data["message"])) {
            $status = "review";
        }
        if (is_string_empty($data["senderName"]) || is_string_empty($data["senderEmail"]) ||
            is_string_empty($data["message"]) || is_string_empty($data["consent"])) {
            $status = "incomplete";
        }
        if (!is_string_empty($data["senderEmail"]) && !filter_var($data["senderEmail"], FILTER_VALIDATE_EMAIL)) $status = "invalid";
        if (is_string_empty($data["userEmail"]) || !filter_var($data["userEmail"], FILTER_VALIDATE_EMAIL)) $status = "unavailable";
        if ($status=="send") $status = $this->yellow->toolbox->validate("contact", $status, $data);
        return array($status, $data);
    }
    
    // Send email message to contact person
    public function sendMail($data) {
        $senderName = $data["senderName"];
        $senderEmail = $data["senderEmail"];
        $userName = $data["userName"];
        $userEmail = $data["userEmail"];
        $sitename = $this->yellow->system->get("sitename");
        $siteEmail = $this->yellow->system->get("from");
        $message = $data["message"];
        $header = $this->getMailHeader($senderName, $senderEmail);
        $footer = $this->getMailFooter($data["referer"], $this->yellow->page->get("title"));
        $mailHeaders = array(
            "To" => $this->yellow->lookup->normaliseAddress("$userName <$userEmail>"),
            "From" => $this->yellow->lookup->normaliseAddress("$sitename <$siteEmail>"),
            "Reply-To" => $this->yellow->lookup->normaliseAddress("$senderName <$senderEmail>"),
            "Subject" => $data["subject"],
            "Date" => date(DATE_RFC2822),
            "Mime-Version" => "1.0",
            "Content-Type" => "text/plain; charset=utf-8",
            "X-Referer-Url" => $data["referer"],
            "X-Request-Url" => $this->yellow->page->getUrl());
        if ($data["spam"]) {
            $mailHeaders["Subject"] = $this->yellow->language->getText("contactMailSpam")." ".$data["subject"];
            $mailHeaders["X-Spam-Flag"] = "YES";
            $mailHeaders["X-Spam-Status"] = "Yes, score=1";
        }
        $mailMessage = "$header\r\n\r\n$message\r\n-- \r\n$footer";
        return $this->yellow->toolbox->mail("contact", $mailHeaders, $mailMessage) ? "done" : "error";
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
    public function getMailFooter($url, $titleDefault) {
        $footer = $this->yellow->language->getText("contactMailFooter");
        $footer = str_replace("\\n", "\r\n", $footer);
        $footer = preg_replace("/@sitename/i", $this->yellow->system->get("sitename"), $footer);
        $footer = preg_replace("/@title/i", $this->findTitle($url, $titleDefault), $footer);
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
