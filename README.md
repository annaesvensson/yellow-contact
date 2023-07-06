<p align="right"><a href="README-de.md">Deutsch</a> &nbsp; <a href="README.md">English</a> &nbsp; <a href="README-sv.md">Svenska</a></p>

# Contact 0.8.23

Email contact page.

<p align="center"><img src="contact-screenshot.png?raw=true" alt="Screenshot"></p>

## How to install an extension

[Download ZIP file](https://github.com/annaesvensson/yellow-contact/archive/main.zip) and copy it into your `system/extensions` folder. [Learn more about extensions](https://github.com/annaesvensson/yellow-update).

## How to use a contact page

The contact page is available on your website as `http://website/contact/`. The webmaster's email is defined in file `system/extensions/yellow-system.ini`. You can set a different `Author` and `Email` in the [page settings](https://github.com/annaesvensson/yellow-core#settings-page) at the top of a page. To show a contact form on your website use a `[contact]` shortcut.

## How to restrict a contact page

If you don't want that messages are sent to any contact person, then restrict emails. Open file `system/extensions/yellow-system.ini` and change `ContactEmailRestriction: 1`. All contact messages go directly to the webmaster and it's no longer possible to set a different contact person in the [page settings](https://github.com/annaesvensson/yellow-core#settings-page) at the top of a page.

If you don't want that messages with links are sent, then restrict links. Open file `system/extensions/yellow-system.ini` and change `ContactLinkRestriction: 1`. Contact messages must not contain clickable links, this blocks many unwanted messages. You can also configure keywords in the spam filter, fortunately, many spammers send the same message multiple times.

## Examples

Showing a contact form:

    [contact]
    [contact /contact/]
    [contact /en/contact/]

Content file with contact form:

    ---
    Title: Example page
    ---
    Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut 
    labore et dolore magna pizza. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris 
    nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit 
    esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt 
    in culpa qui officia deserunt mollit anim id est laborum.

    [contact]

Content file with link to contact page:

    ---
    Title: Example page
    ---
    Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut 
    labore et dolore magna pizza. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris 
    nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit 
    esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt 
    in culpa qui officia deserunt mollit anim id est laborum.

    [Contact a human](/contact/).

Content file with a different contact person on the contact page:

     ---
     Title: Contact a human
     TitleSlug: Contact
     Layout: contact
     Status: unlisted
     Author: Anna Svensson
     Email: anna@svensson.com
     ---

Configuring different spam filters in the settings:

    ContactSpamFilter: advert|promot|market|click here
    ContactSpamFilter: advert|buy|likes|followers|subscribers
    ContactSpamFilter: advert|buy|sell|discount|search engine optimisation

## Settings

The following settings can be configured in file `system/extensions/yellow-system.ini`:

`Author` = name of the webmaster  
`Email` = email of the webmaster  
`ContactSiteEmail` = email of the website, used for generated messages  
`ContactLocation` = contact page location  
`ContactEmailRestriction` = enable email restriction, 1 or 0  
`ContactLinkRestriction` = enable link restriction, 1 or 0  
`ContactSpamFilter` = spam filter as regular expression, `none` to disable  

The following files can be customised:

`system/layouts/contact.html` = layout file for contact page  

## Developer

Anna Svensson. [Get help](https://datenstrom.se/yellow/help/).
