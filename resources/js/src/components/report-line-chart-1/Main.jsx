import { Chart } from "@/base-components";
import { colors } from "@/lib";
import PropTypes from "prop-types";
import { useRecoilValue } from "recoil";
import { colorScheme as colorSchemeStore } from "@/stores/color-scheme";
import { darkMode as darkModeStore } from "@/stores/dark-mode";
import { useRef, useMemo } from "react";

function Main(props) {
  const reportLineChart1Ref = useRef();
  const darkMode = useRecoilValue(darkModeStore);
  const colorScheme = useRecoilValue(colorSchemeStore);

  const getGradient = (el) => {
    const ctx = el.getContext("2d");
    const gradient = ctx.createLinearGradient(0, 0, 0, 400);
    gradient.addColorStop(0, colors.primary(0.11));
    gradient.addColorStop(1, darkMode ? "#28344e00" : "#ffffff01");
    return gradient;
  };

  const data = useMemo(() => {
    return {
      labels: [
        "Jan",
        "Feb",
        "Mar",
        "Apr",
        "May",
        "Jun",
        "Jul",
        "Aug",
        "Sep",
        "Oct",
        "Nov",
        "Dec",
      ],
      datasets: [
        {
          data: [0, 350, 250, 200, 500, 450, 850, 1050, 950, 1100, 900, 1200],
          borderWidth: 1,
          borderColor: colorScheme ? colors.primary(0.7) : "",
          pointRadius: 3.5,
          pointBorderColor: colorScheme ? colors.primary(0.7) : "",
          pointBackgroundColor: darkMode ? colors.darkmode["400"]() : "#ffffff",
          backgroundColor: reportLineChart1Ref.current
            ? getGradient(reportLineChart1Ref.current)
            : "transparent",
          tension: 0.3,
          fill: true,
        },
      ],
    };
  });

  const options = useMemo(() => {
    return {
      maintainAspectRatio: false,
      plugins: {
        legend: {
          display: false,
        },
      },
      scales: {
        x: {
          ticks: {
            font: {
              size: 12,
            },
            color: colors.slate["500"](0.8),
          },
          grid: {
            display: false,
            drawBorder: false,
          },
        },
        y: {
          ticks: {
            font: {
              size: 12,
            },
            color: colors.slate["500"](0.8),
            callback: function (value, index, values) {
              return "$" + value;
            },
          },
          grid: {
            color: darkMode ? colors.slate["500"](0.3) : colors.slate["300"](),
            borderDash: [2, 2],
            drawBorder: false,
          },
        },
      },
    };
  });

  return (
    <Chart
      type="line"
      width={props.width}
      height={props.height}
      data={data}
      options={options}
      className={props.className}
      getRef={(el) => {
        reportLineChart1Ref.current = el;
      }}
    />
  );
}

Main.propTypes = {
  width: PropTypes.oneOfType([PropTypes.number, PropTypes.string]),
  height: PropTypes.oneOfType([PropTypes.number, PropTypes.string]),
  className: PropTypes.string,
};

Main.defaultProps = {
  width: "auto",
  height: "auto",
  className: "",
};

export default Main;
