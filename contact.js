// Contact extension, https://github.com/annaesvensson/yellow-contact

var initContactFromDOM = function() {
    
    // Submit contact form
    var onSubmit = function(e) {
        e.stopPropagation();
        e.preventDefault();
        var elementForm = e.target;
        var elementInput = document.createElement("input");
        elementInput.setAttribute("type", "hidden");
        elementInput.setAttribute("name", "token");
        elementInput.setAttribute("value", generateBrowserToken());
        elementForm.appendChild(elementInput);
        elementForm.submit();
    }

    // Generate browser token
    function generateBrowserToken() {
        var part1 = calculateChecksum(document.title);
        var part2 = calculateChecksum(navigator.userAgent);
        var byteArray = new Uint8Array(4);
        if (window.crypto.getRandomValues) window.crypto.getRandomValues(byteArray);
        var part3 = Array.from(byteArray, function(byte) { return byte.toString(16).padStart(2, "0"); }).join("");
        return part1+part2+part3;
    }
    
    // Calculate 32 bit checksum
    function calculateChecksum(text) {
        var checksum = 0;
        for (var i=0, l=text.length; i<l; i++) checksum += (text.charCodeAt(i) * (i+1));
        return (checksum & 0xffffffff).toString(16).padStart(8, "0");
    }
    
    // Bind events to contact forms
    var elements = document.querySelectorAll(".contact-form");
    for (var i=0, l=elements.length; i<l; i++) {
        elements[i].onsubmit = onSubmit;
    }
};

window.addEventListener("DOMContentLoaded", initContactFromDOM, false);
