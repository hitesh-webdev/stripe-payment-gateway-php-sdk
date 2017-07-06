<?php
/*
	Note: The card details should never be submitted to the server, so their input tags must never have a name attribute.
*/

// Needs Session to be started

//require_once("./config.php");

session_start();

// Keys

define('STRIPE_PRIVATE_KEY', 'sk_test_CS6uf4FPemjrlriJdZrNh2aF');
define('STRIPE_PUBLIC_KEY', 'pk_test_09uLg4EKpmdQRFGwuild6DV5');

?>

<html lang="en">

	<head>
		<meta charset="utf-8">
		<script type="text/javascript" src="https://js.stripe.com/v2/"></script>
		<!-- Set the Stripe key -->
		<script type="text/javascript">Stripe.setPublishableKey("<?=STRIPE_PUBLIC_KEY?>");</script>
	</head>

<?php

// Check for a form submission:
if ($_SERVER['REQUEST_METHOD'] == 'POST') {

	// Stores errors:
	$errors = array();

	// Need a payment token:
	if (isset($_POST['stripeToken'])) {

		$token = $_POST['stripeToken'];

		// Check for a duplicate submission, just in case:
		if (isset($_SESSION['token']) && ($_SESSION['token'] == $token)) {

			$errors['token'] = 'You have apparently resubmitted the form. Please do not do that.';

		} 
		else { // New submission.

			$_SESSION['token'] = $token;

		}

	}
	else {
		$errors['token'] = 'The order cannot be processed. Please make sure you have JavaScript enabled and try again.';
	}


	$amount = 2000; // $20, in cents

	// If no errors, process the order:
	if (empty($errors)) {

		// create the charge on Stripe's servers - this will charge the user's card
		try {

			// Include the Stripe library:
			// Assumes you've installed the Stripe PHP library using Composer!
			require_once('./stripe-php/init.php');

			// set your secret key: remember to change this to your live secret key in production
			// see your keys here https://manage.stripe.com/account
			\Stripe\Stripe::setApiKey(. '"' . STRIPE_PRIVATE_KEY . '"' .);

			/* Setting email for testing (remove) */

			$email = "demo@gmail.com";

			// Charge the order:
			$charge = \Stripe\Charge::create(array(
				"amount" => $amount, // amount in cents, again
				"currency" => "usd",
				"source" => $token,
				"description" => $email
				)
			);

			// Check that it was paid:
			if ($charge->paid == true) {

				// Store the order in the database.
				// Send the email.
				// Celebrate!

				echo "Payment Successfull";

			} else { // Charge was not paid!
				echo '<div class="alert alert-error"><h4>Payment System Error!</h4>Your payment could NOT be processed (i.e., you have not been charged) because the payment system rejected the transaction. You can try again or use another card.</div>';
			}

		} catch (\Stripe\Error\Card $e) {
		    // Card was declined.
			$e_json = $e->getJsonBody();
			$err = $e_json['error'];
			$errors['stripe'] = $err['message'];
		} catch (\Stripe\Error\ApiConnection $e) {
		    // Network problem, perhaps try again.
		} catch (\Stripe\Error\InvalidRequest $e) {
		    // You screwed up in your programming. Shouldn't happen!
		} catch (\Stripe\Error\Api $e) {
		    // Stripe's servers are down!
		} catch (\Stripe\Error\Base $e) {
		    // Something else that's not the customer's fault.
		}

	} // A user form submission error occurred, handled below.

} // Form submission.



?>

<body>
	
	<form action="" method="post" onsubmit="return validate_card()" id="card_details">
		Card Number: <input type="text" id="card_number"><br>
		CVV: <input type="text" id="card_cvv"><br>
		Expiry Month: <input type="text" id="card_month"><br>
		Expiry Year: <input type="text" id="card_year"><br>
		<input type="submit" value="Pay"/>
	</form>

	<p>4242424242424242</p>

	<script type="text/javascript">
		
		function validate_card(){

			var card_number = document.getElementById("card_number");
			var card_cvv = document.getElementById("card_cvv");
			var card_month = document.getElementById("card_month");
			var card_year = document.getElementById("card_year");

			if(card_number.value == ""){
				alert("Enter card number");
				return false;
			}

			if(card_cvv.value == ""){
				alert("Enter card CVV");
				return false;
			}

			if(card_month.value == ""){
				alert("Enter card expiry month");
				return false;
			}

			if(card_year.value == ""){
				alert("Enter card expiry year");
				return false;
			}

			// Stripe JS validation methods

			if (!Stripe.card.validateCardNumber(card_number.value)) {
				alert("The credit card number appears to be invalid.");
				return false;
			}

			if (!Stripe.card.validateCVC(card_cvv.value)) {
				alert("The CVC number appears to be invalid.");
				return false;
			}

			if (!Stripe.card.validateExpiry(card_month.value, card_year.value)) {
				alert("The expiration date appears to be invalid.");
				return false;
			}

			// If all goes OK then generate Token

			Stripe.card.createToken({
				number: card_number.value,
				cvc: card_cvv.value,
				exp_month: card_month.value,
				exp_year: card_year.value
			}, stripeResponseHandler);

			// Prevent form submission

			return false;

		}

		function stripeResponseHandler(status, response) {

			if (response.error) {

				console.log(response.error.message);

			}
			else{

				console.log(response);

				var card_details = document.getElementById("card_details");

				// Token contains id, last4, and card type:
	  			var token = response['id'];

	  			// Insert the token into the form so it gets submitted to the server

	  			var input = document.createElement("input");
	  			input.type = "hidden";
	  			input.name = "stripeToken"
	  			input.value = token; 

	  			card_details.appendChild(input);

	  			card_details.submit();

			}

		}

	</script>

</body>

</html>