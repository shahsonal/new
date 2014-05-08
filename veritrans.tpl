<html>
    <body>
    <form action="{$data.redirect_url}" method="post" id='form'>
    <input type="hidden" name="MERCHANT_ID" value="{$data.merchant_id}" />
    <input type="hidden" name="ORDER_ID" value="{$data.order_id}" />
    <input type="hidden" name="TOKEN_BROWSER" value="{$data.token_browser}" />
    </form>
{literal}
	<script type="text/javascript">
		function submitForm(){			
			document.getElementById("form").submit();
		}
		submitForm();
	</script>
{/literal}
</html>

