// login_script.js
document.addEventListener("DOMContentLoaded", function () {
  const findButton = document.getElementById("find-button");
  const registerButton = document.getElementById("register-button");

  if (findButton) {
    findButton.addEventListener("click", function () {
      location.href = "../find/find.php";
    });
  }

  if (registerButton) {
    registerButton.addEventListener("click", function () {
      location.href = "../register/register.php";
    });
  }
});
