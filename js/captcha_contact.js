        jQuery(document).ready(function($){
            
               //Insertion de la div qui va contenir le captcha dans la page 
               $("input[name='submitMessage']").before("<div id=\"g-recaptcha-response\"></div>");
               
               //Gestion de la soumission du formulaire
                $("#submitMessage").click(function(){
                    //Si pas de réponse du formulaire on affiche un message d\'erreur :
                    if ( ! grecaptcha.getResponse() ) {
					    $.ajax({
								method : "POST",
								url : checkCaptchaUrl,
								data : "action=display_captcha_error",
								success : function(msg){
									$(".alert").remove();	
									$("form.contact-form-box").before(msg);
								}
							});
                       
                        return false;
                    }
                });
            });
      



