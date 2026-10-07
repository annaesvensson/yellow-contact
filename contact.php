<?php
// Contact extension, https://github.com/annaesvensson/yellow-contact

class YellowContact {
    const VERSION = "1.0.5";
    public $yellow;         // access to API
    
    // Handle initialisation
    public function onLoad($yellow) {
        $this->yellow = $yellow;
        $this->yellow->system->setDefault("contactMailDailyLimit", "100");
        $this->yellow->system->setDefault("contactFormRestriction", "0");
        $this->yellow->system->setDefault("contactLinkProtection", "0");
        $this->yellow->system->setDefault("contactTimerProtection", "1");
        $this->yellow->system->setDefault("contactBotProtection", "1");
        $this->yellow->system->setDefault("contactSpamFilter", "advert|promot|market|click here");
    }
    
    // Handle update
    public function onUpdate($action) {
        if ($action=="clean" || $action=="daily" || $action=="uninstall") {
            $fileName = $this->yellow->system->get("coreWorkerDirectory")."contact-mail-delivery.ini";
            if (is_file($fileName) && !$this->yellow->toolbox->deleteFile($fileName)) {
                $this->yellow->toolbox->log("error", "Can't delete file '$fileName'!");
            }
        }
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
            if (!$page->isRequest("timer")) {
                $page->setRequest("timer", "192".substru(time(),-5));
                $page->setHeader("Last-Modified", $this->yellow->toolbox->getHttpDateFormatted(time()));
                $page->setHeader("Cache-Control", "no-cache, no-store");
            }
            if ($page->getRequest("status")=="send") {
                list($status, $data) = $this->validateInputData($page);
                if ($status=="ok") $status = $this->sendMail($data);
                if ($status=="bot") $page->error(444);
                if ($status=="error") $page->error(500, "Can't send email message!");
                $page->setHeader("Last-Modified", $this->yellow->toolbox->getHttpDateFormatted(time()));
                $page->setHeader("Cache-Control", "no-cache, no-store");
                $page->set("status", $status);
            } else {
                $page->set("status", "none");
            }
        }
    }
    
    // Handle page extra data
    public function onParsePageExtra($page, $name) {
        $output = null;
        if ($name=="header") {
            $assetLocation = $this->yellow->system->get("coreServerBase").$this->yellow->system->get("coreAssetLocation");
            $output = "<script defer=\"defer\" src=\"{$assetLocation}contact.js\"></script>\n";
        }
        return $output;
    }

    // Validate input data
    public function validateInputData($page) {
        $status = "ok";
        $data = array(
            "senderName" => trim(preg_replace("/[^\pL\d\-\. ]/u", "-", $page->getRequest("name"))),
            "senderEmail" => trim($page->getRequest("email")),
            "message" => trim($page->getRequest("message")),
            "consent" => trim($page->getRequest("consent")),
            "referer" => trim($page->getRequest("referer")),
            "timer" => trim($page->getRequest("timer")),
            "token" => trim($page->getRequest("token")),
            "subject" => $page->get("title"),
            "userName" => $this->yellow->system->get("author"),
            "userEmail" => $this->yellow->system->get("email"),
            "spam" => false);
        if (is_string_empty($data["token"])) $data["token"] = "none";
        if ($page->isExisting("author") && !$this->yellow->system->get("contactFormRestriction")) {
            $data["userName"] = $page->get("author");
        }
        if ($page->isExisting("email") && !$this->yellow->system->get("contactFormRestriction")) {
            $data["userEmail"] = $page->get("email");
        }
        if ($this->yellow->system->get("contactSpamFilter")!="none") {
            $regex = "/".$this->yellow->system->get("contactSpamFilter")."/i";
            $data["spam"] = preg_match($regex, $data["message"]);
        }
        if ($this->yellow->system->get("contactLinkProtection") && $this->checkClickableLink($data["message"])) {
            $status = "review";
        }
        if ($this->yellow->system->get("contactTimerProtection") && !$this->checkTimeElapsed($data["timer"])) {
            $status = "incomplete";
        }
        if (!is_string_empty($data["senderEmail"]) && !filter_var($data["senderEmail"], FILTER_VALIDATE_EMAIL)) $status = "invalid";
        if (is_string_empty($data["userEmail"]) || !filter_var($data["userEmail"], FILTER_VALIDATE_EMAIL)) $status = "unavailable";
        if (is_string_empty($data["senderName"]) || is_string_empty($data["senderEmail"]) ||
            is_string_empty($data["message"]) || is_string_empty($data["consent"])) {
            $status = "incomplete";
        }
        if ($this->yellow->system->get("contactMailDailyLimit")!=0 && !$this->checkDailyLimit()) {
            $status = "inactive";
        }
        if ($this->yellow->system->get("contactBotProtection") && !$this->checkBrowserToken($data["token"])) {
            $status = "bot";
        }
        if ($status=="ok") $status = $this->yellow->toolbox->validate("contact", $data);
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
            "X-Time-Elapsed" => $this->getTimeElapsed($data["timer"])." second(s)",
            "X-Browser-Token" => $data["token"],
            "X-Referer-Url" => $data["referer"],
            "X-Request-Url" => $this->yellow->page->getUrl());
        if ($data["spam"]) {
            $mailHeaders["Subject"] = $this->yellow->language->getText("contactMailSpam")." ".$data["subject"];
            $mailHeaders["X-Spam-Flag"] = "YES";
            $mailHeaders["X-Spam-Status"] = "Yes, score=1";
        }
        $mailMessage = "$header\r\n\r\n$message\r\n-- \r\n$footer";
        $status = $this->yellow->toolbox->mail("contact", $mailHeaders, $mailMessage) ? "done" : "error";
        $this->writeMailDelivery($status, "Send email message from $senderName <$senderEmail> to $userName <$userEmail>");
        return $status;
    }
    
    // Write sucessful email delivery to file
    public function writeMailDelivery($status, $message) {
        if ($status=="done") {
            $fileName = $this->yellow->system->get("coreWorkerDirectory")."contact-mail-delivery.ini";
            $line = date("Y-m-d H:i:s")." info ".trim($message)."\n";
            $this->yellow->toolbox->appendFile($fileName, $line);
        }
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
    
    // Return elapsed time in seconds
    public function getTimeElapsed($timer) {
        return substru($timer, 0, 3)!="192" ? 0 : abs(substru(time(),-5) - substru($timer, 3, 5));
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

    // Check browser token
    public function checkBrowserToken($token) {
        return strlenu($token)==24;
    }
    
    // Check if mail delivery has reached daily limit
    public function checkDailyLimit() {
        $fileName = $this->yellow->system->get("coreWorkerDirectory")."contact-mail-delivery.ini";
        $fileData = $this->yellow->toolbox->readFile($fileName);
        $lines = $this->yellow->toolbox->getTextLines($fileData);
        return count($lines)<$this->yellow->system->get("contactMailDailyLimit");
    }
    
    // Check if time is within resonable limits
    public function checkTimeElapsed($timer) {
        $seconds = $this->getTimeElapsed($timer);
        return $seconds>=10 && $seconds<=43200;
    }

    // Check if text contains clickable links
    public function checkClickableLink($text) {
        $found = false;
        foreach (preg_split("/\s+/", $text) as $token) {
            if (preg_match("/([\w\-\.]{2,}\.[\w]{2,})/", $token)) $found = true;
            if (preg_match("/^\w+:\/\//", $token)) $found = true;
        }
        return $found;
    }
}
