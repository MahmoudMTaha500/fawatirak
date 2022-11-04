const initPaymentMethodChange = () => {
    let walletContainer = document.querySelector(
        ".fawaterk-mobile-waller-container"
    );
    let walletContainerCloseButton =
        walletContainer.querySelector(".close-field");
    let submitWallerContainerField =
        walletContainer.querySelector(".submit-field");
    var wallet_button = walletContainer.querySelector("#wallet_btn");

    wallet_button.addEventListener("click", function() {
        alert("thatnk you,  please complete your prosses  !");
    });
    let walletContainerStatus = false;
    // Check if the wallet container is filled when changing payment method
    jQuery("body").on("payment_method_selected", function(event) {
        let getCurrentPaymentMethod = document.querySelector(
            '#payment ul.payment_methods > li[class*="_mobile_wallet"] input[type="radio"]:checked'
        );
        if (getCurrentPaymentMethod != null) {
            if (!walletContainerStatus) {
                walletContainer.classList.remove("hidden");
                walletContainerStatus = true;
            }
        }
    });
    // Close wallet container
    walletContainerCloseButton != null &&
        walletContainerCloseButton.addEventListener("click", (e) => {
            walletContainer.classList.add("hidden");
            walletContainerStatus = false;
        });
    submitWallerContainerField != null &&
        submitWallerContainerField.addEventListener("click", (e) => {
            walletContainer.classList.add("hidden");
            walletContainerStatus = false;
        });
};
document.addEventListener("DOMContentLoaded", () => {
    initPaymentMethodChange();
});