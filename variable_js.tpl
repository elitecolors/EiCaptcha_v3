<script type="text/javascript">
	 var publicKey = "{$publicKey}";
	 var checkCaptchaUrl = "{$checkCaptchaUrl}";
</script>

<script src="https://www.google.com/recaptcha/api.js?render={$publicKey}"></script>
  <script>
        grecaptcha.ready(function () {
            grecaptcha.execute({$publicKey}, { action: 'validate_captcha' }).then(function (token) {
                var recaptchaResponse = document.getElementById('g-recaptcha-response');
                recaptchaResponse.value = token;
            });
        });
    </script>