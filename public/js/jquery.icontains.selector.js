// An implementation of a case-insensitive contains pseudo
// made for all versions of jQuery
(function( $ ) {

function icontains( elem, text ) {
    return (
        elem.textContent ||
        elem.innerText ||
        $( elem ).text() ||
        ""
    ).toLowerCase().indexOf( (text || "").toLowerCase() ) > -1;
}

$.expr[':'].icontains = $.expr.createPseudo ?
    $.expr.createPseudo(function( text ) {
        return function( elem ) {
            return icontains( elem, text );
        };
    }) :
    function( elem, i, match ) {
        return icontains( elem, match[3] );
    };

})( jQuery );