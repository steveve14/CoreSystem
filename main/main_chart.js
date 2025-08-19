/**
 * 월별 통계 막대 그래프를 렌더링합니다.
 * @param {string} canvasId - 그래프를 그릴 canvas 요소의 ID
 * @param {string[]} labels - X축에 표시될 카테고리 이름 배열
 * @param {number[]} incomeData - 각 카테고리별 수입 데이터 배열
 * @param {number[]} expenseData - 각 카테고리별 지출 데이터 배열
 */
function renderMonthlyChart(canvasId, labels, incomeData, expenseData) {
  const ctx = document.getElementById(canvasId);
  if (!ctx) {
    console.error("Chart canvas element not found with ID:", canvasId);
    return;
  }

  new Chart(ctx, {
    type: "bar", // 막대 그래프
    data: {
      labels: labels,
      datasets: [
        {
          label: "수입",
          data: incomeData,
          backgroundColor: "rgba(75, 192, 192, 0.6)",
          borderColor: "rgba(75, 192, 192, 1)",
          borderWidth: 1,
        },
        {
          label: "지출",
          data: expenseData,
          backgroundColor: "rgba(255, 99, 132, 0.6)",
          borderColor: "rgba(255, 99, 132, 1)",
          borderWidth: 1,
        },
      ],
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      scales: {
        y: {
          beginAtZero: true,
          ticks: {
            color: "#94a3b8", // Y축 눈금 색상
            callback: function (value) {
              // Y축 눈금에 '원' 단위 표시
              return "₩ " + new Intl.NumberFormat().format(value);
            },
          },
          grid: {
            color: "rgba(148, 163, 184, 0.15)", // Y축 그리드 라인 색상
          },
        },
        x: {
          ticks: {
            color: "#94a3b8", // X축 라벨 색상
          },
          grid: {
            display: false, // X축 그리드 라인 숨김
          },
        },
      },
      plugins: {
        legend: {
          labels: {
            color: "#e5e7eb", // 범례(legend) 텍스트 색상
          },
        },
        tooltip: {
          callbacks: {
            // 툴팁(마우스 올렸을 때)에 '원' 단위 표시
            label: function (context) {
              let label = context.dataset.label || "";
              if (label) {
                label += ": ";
              }
              if (context.parsed.y !== null) {
                label +=
                  "₩ " + new Intl.NumberFormat().format(context.parsed.y);
              }
              return label;
            },
          },
        },
      },
    },
  });
}
