<p align="right"><a href="README-de.md">Deutsch</a> &nbsp; <a href="README.md">English</a> &nbsp; <a href="README-sv.md">Svenska</a></p>

# Contact 0.8.21

E-Mail-Kontaktseite.

<p align="center"><img src="contact-screenshot.png?raw=true" alt="Bildschirmfoto"></p>

## Wie man eine Erweiterung installiert

[ZIP-Datei herunterladen](https://github.com/annaesvensson/yellow-contact/archive/main.zip) und in dein `system/extensions`-Verzeichnis kopieren. [Weitere Informationen zu Erweiterungen](https://github.com/annaesvensson/yellow-update/tree/main/README-de.md).

## Wie man eine Kontaktseite benutzt

Die Kontaktseite ist auf deiner Webseite vorhanden als `http://website/contact/`. Die E-Mail des Webmasters wird in der Datei `system/extensions/yellow-system.ini` festgelegt. Ganz oben auf einer Seite kannst du einen anderen `Author` und `Email` in den [Seiteneinstellungen](https://github.com/annaesvensson/yellow-core/tree/main/README-de.md#einstellungen-seite) festlegen. Um ein Kontaktformular auf deiner Webseite anzuzeigen, benutze eine `[contact]`-Abkürzung.

## Wie man eine Kontaktseite beschränkt

Falls du nicht willst dass Nachrichten an beliebige Kontaktpersonen geschickt werden, beschränke E-Mails. Öffne die Datei `system/extensions/yellow-system.ini` und ändere `ContactEmailRestriction: 1`. Alle Kontaktnachrichten gehen dann direkt an den Webmaster und es nicht mehr möglich eine andere Kontaktperson in den [Seiteneinstellungen](https://github.com/annaesvensson/yellow-core/tree/main/README-de.md#einstellungen-seite) ganz oben auf einer Seite festzulegen.

Falls du nicht willst dass Nachrichten mit Links verschickt werden, beschränke Links. Öffne die Datei `system/extensions/yellow-system.ini` und ändere `ContactLinkRestriction: 1`. Kontaktnachrichten dürfen dann keine anklickbare Links enthalten, das blockiert viele unerwünschte Nachrichten. Du kannst ausserdem Stichwörter im Spamfilter einstellen, netterweise schicken viele Spammer die selbe Nachricht mehrfach.

## Beispiele

Kontaktformular anzeigen:

    [contact]
    [contact /contact/]
    [contact /de/contact/]

Inhaltsdatei mit Kontaktformular:

    ---
    Title: Beispielseite
    ---
    Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut 
    labore et dolore magna pizza. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris 
    nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit 
    esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt 
    in culpa qui officia deserunt mollit anim id est laborum.

    [contact]

Inhaltsdatei mit Link zur Kontaktseite:

    ---
    Title: Beispielseite
    ---    
    Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut 
    labore et dolore magna pizza. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris 
    nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit 
    esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt 
    in culpa qui officia deserunt mollit anim id est laborum.
    
    [Kontaktiere einen Menschen](/contact/).

Inhaltsdatei mit einer anderen Kontaktperson auf der Kontaktseite:

     ---
     Title: Kontaktiere einen Menschen
     TitleSlug: Contact
     Layout: contact
     Status: unlisted
     Author: Anna Svensson
     Email: anna@svensson.com
     ---

Verschiedene Spamfilter in den Einstellungen festlegen:

    ContactSpamFilter: advert|promot|market|click here
    ContactSpamFilter: advert|promot|market|click here|youtube|instagram|twitter
    ContactSpamFilter: werbung|kaufe|rabatt|singles in deiner nähe|suchmaschinenoptimierung

## Einstellungen

Die folgenden Einstellungen können in der Datei `system/extensions/yellow-system.ini` vorgenommen werden:

`Author` = Name des Webmasters  
`Email` = E-Mail des Webmasters  
`ContactSiteEmail` = E-Mail der Webseite, wird für erstellte Nachrichten angewendet  
`ContactLocation` = Ort der Kontaktseite  
`ContactEmailRestriction` = E-Mail-Beschränkung aktivieren, 1 oder 0  
`ContactLinkRestriction` = Linkbeschränkung aktivieren, 1 oder 0  
`ContactSpamFilter` = Spamfilter als regulärer Ausdruck, `none` um zu deaktivieren  

Die folgenden Dateien können angepasst werden:

`system/layouts/contact.html` = Layoutdatei für Kontaktseite  

## Entwickler

Anna Svensson. [Hilfe finden](https://datenstrom.se/de/yellow/help/).
