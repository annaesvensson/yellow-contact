<?php
// Contact extension, https://github.com/annaesvensson/yellow-contact

class YellowContact {
    const VERSION = "1.0.8";
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
                $page->setRequest("timer", "192".substru(time(), -5));
                $page->setHeader("Last-Modified", $this->yellow->toolbox->getHttpDateFormatted(time()));
                $page->setHeader("Cache-Control", "no-cache, no-store");
            }
            if ($page->getRequest("status")=="send") {
                list($status, $data) = $this->validateInputData($page);
                if ($status=="ok") $status = $this->sendMail($page, $data);
                $page->setHeader("Last-Modified", $this->yellow->toolbox->getHttpDateFormatted(time()));
                $page->setHeader("Cache-Control", "no-cache, no-store");
                $page->set("status", $status);
            } else {
                $page->set("status", $this->checkMailDelivery() ? "none" : "closed");
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
        $status = $this->checkMailDelivery() ? "ok" : "closed";
        $senderName = $this->yellow->lookup->normaliseInput($page->getRequest("name"), "name");
        $senderEmail = $this->yellow->lookup->normaliseInput($page->getRequest("email"));
        $message = $this->yellow->lookup->normaliseInput($page->getRequest("message"), "textarea");
        $consent = $this->yellow->lookup->normaliseInput($page->getRequest("consent"));
        $referer = $this->yellow->lookup->normaliseInput($page->getRequest("referer"));
        $timer = $this->yellow->lookup->normaliseInput($page->getRequest("timer"));
        $token = $this->yellow->lookup->normaliseInput($page->getRequest("token"));
        $userName = $this->getUserName($page);
        $userEmail = $this->getUserEmail($page);
        if (!$this->checkBrowserToken($page)) { $status = "bot"; $page->error(444); }
        if ($status=="ok" && (is_string_empty($senderName) || is_string_empty($senderEmail) || is_string_empty($message) || is_string_empty($consent))) $status = "incomplete";
        if ($status=="ok" && !filter_var($senderEmail, FILTER_VALIDATE_EMAIL)) $status = "invalid";
        if ($status=="ok" && !$this->checkTimerProtection($timer)) $status = "incomplete";
        if ($status=="ok" && !$this->checkLinkProtection($message)) $status = "review";
        if ($status=="ok" && !$this->checkSpamFilter($message)) $status = "spam";
        $data = array(
            "senderName" => $senderName,
            "senderEmail" => $senderEmail,
            "userName" => $userName,
            "userEmail" => $userEmail,
            "message" => $message,
            "consent" => $consent,
            "referer" => $referer,
            "timer" => $timer,
            "token" => $token,
            "spam" => false);
        if ($status=="ok") $status = $this->yellow->toolbox->validate("contact", $data);
        if ($status=="spam") { $status = "ok"; $data["spam"] = true; }
        return array($status, $data);
    }
    
    // Send email message to contact person
    public function sendMail($page, $data) {
        $status = $this->checkMailDelivery() ? "ok" : "closed";
        $senderName = $data["senderName"];
        $senderEmail = $data["senderEmail"];
        $userName = $data["userName"];
        $userEmail = $data["userEmail"];
        $sitename = $this->yellow->system->get("sitename");
        $siteEmail = $this->yellow->system->get("from");
        $subject = $page->get("title");
        $message = $data["message"];
        $header = $this->getMailHeader($senderName, $senderEmail);
        $footer = $this->getMailFooter($data["referer"], $page->get("title"));
        $mailHeaders = array(
            "To" => $this->yellow->lookup->normaliseAddress("$userName <$userEmail>"),
            "From" => $this->yellow->lookup->normaliseAddress("$sitename <$siteEmail>"),
            "Reply-To" => $this->yellow->lookup->normaliseAddress("$senderName <$senderEmail>"),
            "Subject" => $subject,
            "Date" => date(DATE_RFC2822),
            "Mime-Version" => "1.0",
            "Content-Type" => "text/plain; charset=utf-8",
            "X-Request-Url" => $page->getUrl(),
            "X-Browser-Token" => $data["token"],
            "X-Time-Elapsed" => $this->getTimeElapsed($data["timer"])." second(s)");
        if ($data["spam"]) {
            $mailHeaders["Subject"] = $this->yellow->language->getText("contactMailSpam")." ".$subject;
            $mailHeaders["X-Spam-Flag"] = "YES";
            $mailHeaders["X-Spam-Status"] = "Yes, score=1";
        }
        $mailMessage = "$header\r\n\r\n$message\r\n-- \r\n$footer";
        $mailDelivery = date("Y-m-d H:i:s")." info Send email message from $senderName <$senderEmail> to $userName <$userEmail>\n";
        if ($status=="ok") {
            $fileName = $this->yellow->system->get("coreWorkerDirectory")."contact-mail-delivery.ini";
            $status = $this->yellow->toolbox->appendFile($fileName, $mailDelivery) ? "ok" : "error:";
            if ($status=="error") $page->error(500, "Can't write file '$fileName'!");
        }
        if ($status=="ok") {
            $status = $this->yellow->toolbox->mail("contact", $mailHeaders, $mailMessage) ? "done" : "error";
            if ($status=="error") $page->error(500, "Can't send email message!");
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
    public function getMailFooter($url, $titleDefault) {
        $footer = $this->yellow->language->getText("contactMailFooter");
        $footer = str_replace("\\n", "\r\n", $footer);
        $footer = preg_replace("/@sitename/i", $this->yellow->system->get("sitename"), $footer);
        $footer = preg_replace("/@title/i", $this->getPageTitle($url, $titleDefault), $footer);
        return $footer;
    }
    
    // Return page title for URL
    public function getPageTitle($url, $titleDefault) {
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
    
    // Return user name
    public function getUserName($page) {
        $userName = $this->yellow->system->get("author");
        if ($page->isExisting("author") && !$this->yellow->system->get("contactFormRestriction")) {
            $userName = $page->get("author");
        }
        return $userName;
    }
    
    // Return user email
    public function getUserEmail($page) {
        $userEmail = $this->yellow->system->get("email");
        if ($page->isExisting("email") && !$this->yellow->system->get("contactFormRestriction")) {
            $userEmail = $page->get("email");
        }
        return $userEmail;
    }
    
    // Return elapsed time in seconds
    public function getTimeElapsed($timer) {
        return substru($timer, 0, 3)!="192" ? 0 : abs(substru(time(), -5) - substru($timer, 3, 5));
    }
    
    // Calculate challenge from text
    public function calculateChallenge($text) {
        $value = 0;
        $codeunits = unpack("n*", mb_convert_encoding($text, "UTF-16BE", "UTF-8"));
        for ($i=0, $l=count($codeunits); $i<$l; ++$i) $value += $codeunits[$i+1];
        return str_pad(dechex($value & 0xffffffff), 8, "0", STR_PAD_LEFT);
    }
    
    // Check mail delivery
    public function checkMailDelivery() {
        $ok = true;
        if ($this->yellow->system->get("contactMailDailyLimit")!=0) {
            $fileName = $this->yellow->system->get("coreWorkerDirectory")."contact-mail-delivery.ini";
            $fileData = $this->yellow->toolbox->readFile($fileName);
            $lines = $this->yellow->toolbox->getTextLines($fileData);
            if (count($lines)>=$this->yellow->system->get("contactMailDailyLimit")) $ok = false;
        }
        return $ok;
    }

    // Check browser token
    public function checkBrowserToken($page) {
        $ok = true;
        if ($this->yellow->system->get("contactBotProtection")) {
            $token = $this->yellow->lookup->normaliseInput($page->getRequest("token"));
            $ok = strlenu($token)==24 && substru($token, 0, 8)==$this->calculateChallenge($page->get("titleHeader"));
        }
        return $ok;
    }
    
    // Check if time is within resonable limits
    public function checkTimerProtection($timer) {
        $ok = true;
        if ($this->yellow->system->get("contactTimerProtection")) {
            $seconds = $this->getTimeElapsed($timer);
            $ok = $seconds>=10 && $seconds<=43200;
        }
        return $ok;
    }

    // Check if text contains clickable links
    public function checkLinkProtection($text) {
        $ok = true;
        if ($this->yellow->system->get("contactLinkProtection")) {
            foreach (preg_split("/\s+/", $text) as $token) {
                if (preg_match("/([\w\-\.]{2,}\.[\w]{2,})/", $token)) $found = true;
                if (preg_match("/^\w+:\/\//", $token)) $ok = false;
            }
        }
        return $ok;
    }
    
    // Check if text contains spam
    public function checkSpamFilter($text) {
        $ok = true;
        if ($this->yellow->system->get("contactSpamFilter")!="none") {
            $regex = "/".$this->yellow->system->get("contactSpamFilter")."/i";
            if (preg_match($regex, $text)) $ok = false;
        }
        return $ok;
    }
}
