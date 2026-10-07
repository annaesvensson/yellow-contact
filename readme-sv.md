# Contact 1.0.6

Kontaktformulär för att skicka e-post. Utvecklad av Anna Svensson.

<p align="center"><img src="screenshot.png" alt="Skärmdump" /></p>

## Hur man installerar ett tillägg

[Ladda ner ZIP-filen](https://github.com/annaesvensson/yellow-contact/archive/refs/heads/main.zip) och kopiera den till din `system/extensions` mapp. [Läs mer om tillägg](https://github.com/annaesvensson/yellow-update/tree/main/readme-sv.md).

## Hur man använder ett kontaktformulär

Kontaktformuläret finns tillgänglig på din webbplats som `http://website/contact/`. Vanligtvis skickas meddelanden till webmastern. Webmasterns email definieras i filen `system/extensions/yellow-system.ini`. Du kan ställa in en annan `Author` och `Email` i [sidinställningar](https://github.com/annaesvensson/yellow-core/tree/main/readme-sv.md#inställningar-page) högst upp på en sida.

## Hur man begränsar ett kontaktformulär

Om du inte vill att meddelanden skickas till vilken kontaktperson som helst begränsar du kontaktformuläret. Öppna filen `system/extensions/yellow-system.ini` och ändra `ContactFormRestriction: 1`. Alla meddelanden går direkt till webmastern och det är inte längre möjligt att ställa in en annan email i [sidinställningar](https://github.com/annaesvensson/yellow-core/tree/main/readme-sv.md#inställningar-page) högst upp på en sida.

## Hur man skyddar ett kontaktformulär mot reklam

Du kan skydda ditt kontaktformulär mot reklam, skräppost och oönskade meddelanden. De viktigaste skyddsmekanismerna aktiveras som standard, Andra skyddsmekanismer kan aktiveras vid behov. Öppna filen `system/extensions/yellow-system.ini` och ändra `ContactLinkProtection: 1`. Meddelanden får då inte längre innehålla klickbara länkar, detta blockerar många oönskade meddelanden, men tillåter ändå vanliga meddelanden att passera. Du kan också ställa in nyckelord i skräppostfiltret, lyckligtvis skickar många spammare samma meddelande flera gånger.

## Exempel

Innehållsfil för kontaktformulär:

    ---
    Title: Kontakta en människa
    TitleSlug: Contact
    Layout: contact
    Status: unlisted
    ---

Innehållsfil för kontaktformulär med en annan kontaktperson:

    ---
    Title: Kontakta en människa
    TitleSlug: Contact
    Layout: contact
    Status: unlisted
    Author: Anna Svensson
    Email: anna@svensson.com
    ---

Innehållsfil med länk till kontaktsidan:

    ---
    Title: Exempelsida
    ---
    Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut 
    labore et dolore magna pizza. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris 
    nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit 
    esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt 
    in culpa qui officia deserunt mollit anim id est laborum.
    
    [Kontakta en människa](/contact/).

Konfigurera olika skräppostfilter i inställningar:

    ContactSpamFilter: advert|promot|market|click here
    ContactSpamFilter: advert|buy|tokens|likes|followers|subscribers
    ContactSpamFilter: reklam|intelligens|sökmotor|optimering

## Inställningar

Följande inställningar kan konfigureras i filen `system/extensions/yellow-system.ini`:

`Author` = webmasterns namn  
`Email` = webmasterns email  
`From` = email för utgående meddelanden  
`ContactMailDailyLimit` = antal tillåtna e-postleveranser per dag, 0 för obegränsad  
`ContactFormRestriction` = aktivera kontaktformulärbegränsning, 1 eller 0  
`ContactLinkProtection` = aktivera skydd mot klickbara länkar, 1 eller 0  
`ContactTimerProtection` = aktivera skydd mot snabb sändning, 1 eller 0  
`ContactBotProtection` = aktivera skydd mot dåliga botar, 1 eller 0  
`ContactSpamFilter` = skräppostfilter som reguljära uttryck, `none` för att inaktivera  

Följande filer kan anpassas:

`system/layouts/contact.html` = layoutfil för kontaktsida  

Har du några frågor? [Få hjälp](https://datenstrom.se/sv/yellow/help/).
