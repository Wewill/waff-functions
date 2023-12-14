jQuery(function($) {

	// Films 
	$('.post-new-php.post-type-film #titlewrap').append('<p class="title-mentions">\
		<span class="label">UK</span> Please do not fill in UPPERCASE. Respect the regular capitalization rules.<br>\
		<span class="label">FR</span> <em>Merci de ne pas écrire en MAJUSCULES. Respectez une casse normale.</em></p>');

	//Sections
	$('.term-php.post-type-film.taxonomy-section tr.term-description-wrap p.description').hide();
	$('.term-php.post-type-film.taxonomy-section tr.term-description-wrap > td').append('<div class="description wpt-form-description wpt-form-description-textfield description-textfield">\
		<p><span class="label">UK</span> <em>Simple text. This content will be used on website and catalog</em><br>\
		<span class="label">FR</span> Texte non mis en forme. Ce contenu sera utilisé à la fois sur le site et le catalogue</p>\
		<p><span class="important">Informations pour le catalogue :</span><br>\
		<span class="label">UK</span> Markdown available : *italic* **bold** ***label*** #small# ##huge##<br>\
		<span class="label">FR</span> <em>Markdown disponible : *italique* **gras** ***label*** #petit# ##grand##</em></p>\
		<p><span class="important">Exemple de mise en forme :</span><br>\
		<code>Un cycle autour du grand cinéaste Luis Buñel qui réunit le rêve, l’érotisme, la religion, la contestation, l’humour, la provocation…le surréalisme · <em>*A cycle around the great filmmaker Luis Buñel which brings together dreams, eroticism, religion, contestation, humor, provocation … surrealism*</em></code></p>\
		</div>');

	// // Accreditations 
	// var getUrlParameter = function getUrlParameter(sParam) {
	// 	var sPageURL = window.location.search.substring(1),
	// 		sURLVariables = sPageURL.split('&'),
	// 		sParameterName,
	// 		i;
		
	// 	for (i = 0; i < sURLVariables.length; i++) {
	// 		sParameterName = sURLVariables[i].split('=');
			
	// 		if (sParameterName[0] === sParam) {
	// 		return typeof sParameterName[1] === undefined ? true : decodeURIComponent(sParameterName[1]);
	// 		}
	// 	}
	// 	return false;
	// };

	// >>> A PASSER DANS customize post ++++++ #43
	// FAIT == A TESTER DEPUIS customize post
	// Accreditations
	// var express = getUrlParameter('express');
	// // console.log("express", express, $("input[data-wpt-id='wpcf-a-express']"), $("input[data-wpt-id='wpcf-a-express']").prop("checked") );
	// if ( express == '1' ) {
	// 	$('body').addClass('express_accreditation');
	// 	//$("input[data-wpt-id='wpcf-a-express']").prop("checked",true);
	// 	$("input[data-wpt-id='wpcf-a-express']").trigger('click');
	// }

	// var create_accreditation = getUrlParameter('create_accreditation');
	// if ( create_accreditation == '1' ) {
	// 	$('body').addClass('create_accreditation');
	// }
	// if ( $('body').hasClass('create_accreditation') ) {
	// 	$('.wp-heading-inline').prepend('<strong>Create accreditation > </strong>');
	// 	$('.types-related-content-actions').before('<div class="notice notice-info info fade" style="border-left-color:#1aa76e"><p><span class="dashicons dashicons-editor-help" style="color:#1aa76e"></span><strong style="color:#1aa76e">Vous êtes sur le point de créer une accréditation pour ce contact,</strong> vous pourrez remplir les détails en l\'éditant après publication. <em>You are about to create an Accreditation for this contact, you will be able to fill details while editing it after posting</em></p></div>');
	// }

	// // Accredications > posts 
	// $(document).on("click", ".types-related-content-actions input.button", function () {
    //     //alert("You are about to create an Accreditation for this contact, you will be able to fill details while editing it after posting");
	// 	console.log('types-new-post-type-title::', $(this) );
	// 	$('input#types-new-post-type-title').val('Automatic');
	// 	$('input#types-new-post-type-title').trigger('change');
    // });

});