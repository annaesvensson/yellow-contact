# Contact 1.0.1

Kontaktformular zum Versenden von E-Mails. Entwickelt von Anna Svensson.

<p align="center"><img src="screenshot.png" alt="Bildschirmfoto" /></p>

## Wie man eine Erweiterung installiert

[ZIP-Datei herunterladen](https://github.com/annaesvensson/yellow-contact/archive/refs/heads/main.zip) und in dein `system/extensions`-Verzeichnis kopieren. [Weitere Informationen zu Erweiterungen](https://github.com/annaesvensson/yellow-update/tree/main/readme-de.md).

## Wie man ein Kontaktformular benutzt

Das Kontaktformular ist auf deiner Webseite vorhanden als `http://website/contact/`. In der Regel werden alle Nachrichten die im Kontaktformular eingegeben werden an den Webmaster gesendet. Die E-Mail des Webmasters wird in der Datei `system/extensions/yellow-system.ini` festgelegt. Ganz oben auf einer Seite kannst du einen anderen `Author` und `Email` in den [Seiteneinstellungen](https://github.com/annaesvensson/yellow-core/tree/main/readme-de.md#einstellungen-seite) festlegen.

## Wie man ein Kontaktformular beschränkt

Falls du nicht willst dass Nachrichten an beliebige Kontaktpersonen gesendet werden, beschränke E-Mails. Öffne die Datei `system/extensions/yellow-system.ini` und ändere `ContactEmailRestriction: 1`. Alle Nachrichten gehen dann direkt an den Webmaster und es nicht mehr möglich eine andere Kontaktperson in den [Seiteneinstellungen](https://github.com/annaesvensson/yellow-core/tree/main/readme-de.md#einstellungen-seite) ganz oben auf einer Seite festzulegen.

## Wie man ein Kontaktformular vor Spam schützt

Du kannst dein Kontaktformular vor Spam, Bots und Werbung schützen. Öffne die Datei `system/extensions/yellow-system.ini` und ändere `ContactLinkRestriction: 1`. Nachrichten dürfen dann keine anklickbare Links enthalten, das blockiert viele unerwünschte Nachrichten. Du kannst ausserdem Stichwörter im Spamfilter einstellen, netterweise schicken viele Spammer die selbe Nachricht mehrfach.

Wir experimentieren mit zusätzlichen Schutzmechanismen und freuen uns über dein Feedback.

## Beispiele

Inhaltsdatei fürs Kontaktformular

    ---
    Title: Kontaktiere einen Menschen
    TitleSlug: Contact
    Layout: contact
    Status: unlisted
    --- 

Inhaltsdatei fürs Kontaktformular mit einer anderen Kontaktperson:

    ---
    Title: Kontaktiere einen Menschen
    TitleSlug: Contact
    Layout: contact
    Status: unlisted
    Author: Anna Svensson
    Email: anna@svensson.com
    ---

Inhaltsdatei mit Link zum Kontaktformular:

    ---
    Title: Beispielseite
    ---    
    Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut 
    labore et dolore magna pizza. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris 
    nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit 
    esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt 
    in culpa qui officia deserunt mollit anim id est laborum.
    
    [Kontaktiere einen Menschen](/contact/).

Verschiedene Spamfilter in den Einstellungen festlegen:

    ContactSpamFilter: advert|promot|market|click here
    ContactSpamFilter: advert|buy|likes|followers|subscribers
    ContactSpamFilter: advert|kaufe|anbiete|rabatt|suchmaschinenoptimierung

## Einstellungen

Die folgenden Einstellungen können in der Datei `system/extensions/yellow-system.ini` vorgenommen werden:

`Author` = Name des Webmasters  
`Email` = E-Mail des Webmasters  
`From` = E-Mail für ausgehende Nachrichten  
`ContactLocation` = Ort der Kontaktseite  
`ContactEmailRestriction` = E-Mail-Beschränkung aktivieren, 1 oder 0  
`ContactLinkRestriction` = Linkbeschränkung aktivieren, 1 oder 0  
`ContactSpamFilter` = Spamfilter als regulärer Ausdruck, `none` um zu deaktivieren  

Die folgenden Dateien können angepasst werden:

`system/layouts/contact.html` = Layoutdatei für Kontaktseite  

Hast du Fragen? [Hilfe finden](https://datenstrom.se/de/yellow/help/).
