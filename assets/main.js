document.addEventListener("DOMContentLoaded", () => {
	let walletField = document.querySelector("#fawaterk_wallet_number_field");
	const initPaymentMethodChange = () => {
		let paymentMethods = document.querySelectorAll(
			'input[name="payment_method"]'
		);
		paymentMethods.length &&
			paymentMethods.forEach((pm) => {
				pm.addEventListener("change", (e) => {
					if (pm.value.includes("fawaterk")) {
						walletField.classList.add("visible");
					}
				});
			});
	};
});
