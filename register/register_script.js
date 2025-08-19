// register_script.js
document.addEventListener("DOMContentLoaded", function () {
  // 클라이언트 측 비밀번호 강도 실시간 검사
  const passwordInput = document.getElementById("password");
  const passwordHint = document.getElementById("pwHint");

  if (passwordInput && passwordHint) {
    const strengthLabels = [
      "매우 취약",
      "취약",
      "보통",
      "양호",
      "강함",
      "매우 강함",
    ];
    const strengthColors = {
      good: "#34d399",
      medium: "#fbbf24",
      weak: "#f87171",
    };

    function checkPasswordStrength(password) {
      const criteria = [
        password.length >= 8,
        /[A-Z]/.test(password),
        /[a-z]/.test(password),
        /\d/.test(password),
        /[^A-Za-z0-9]/.test(password),
      ];
      return criteria.filter(Boolean).length;
    }

    passwordInput.addEventListener("input", () => {
      const score = checkPasswordStrength(passwordInput.value);
      passwordHint.textContent = "비밀번호 강도: " + strengthLabels[score];
      passwordHint.style.color =
        score >= 4
          ? strengthColors.good
          : score >= 3
          ? strengthColors.medium
          : strengthColors.weak;
    });
  }

  // 폼 제출 전 비밀번호 일치 확인
  const registerForm = document.getElementById("regForm");

  if (registerForm) {
    registerForm.addEventListener("submit", function (event) {
      const pw1 = document.getElementById("password").value;
      const pw2 = document.getElementById("password_confirm").value;

      if (pw1 !== pw2) {
        event.preventDefault(); // 폼 제출 중단
        alert("비밀번호가 일치하지 않습니다. 다시 확인해주세요.");
        document.getElementById("password_confirm").focus();
      }
    });
  }
});
