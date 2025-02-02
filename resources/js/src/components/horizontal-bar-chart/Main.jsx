import { Chart } from "@/base-components";
import { colors } from "@/lib";
import PropTypes from "prop-types";
import { useRecoilValue } from "recoil";
import { useMemo } from "react";
import { colorScheme as colorSchemeStore } from "@/stores/color-scheme";
import { darkMode as darkModeStore } from "@/stores/dark-mode";

function Main(props) {
  const darkMode = useRecoilValue(darkModeStore);
  const colorScheme = useRecoilValue(colorSchemeStore);

  const data = useMemo(() => {
    return {
      labels: ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug"],
      datasets: [
        {
          label: "Html Template",
          barPercentage: 0.4,
          borderWidth: 1,
          data: [0, 200, 250, 200, 500, 450, 850, 1050],
          borderColor: colorScheme ? colors.primary(0.7) : "",
          backgroundColor: colorScheme ? colors.primary(0.2) : "",
        },
        {
          label: "VueJs Template",
          barPercentage: 0.4,
          borderWidth: 1,
          data: [0, 300, 400, 560, 320, 600, 720, 850],
          borderColor: darkMode
            ? colors.darkmode["200"]()
            : colors.slate["400"](),
          backgroundColor: darkMode
            ? colors.darkmode["200"](0.1)
            : colors.slate["400"](0.1),
        },
      ],
    };
  });

  const options = useMemo(() => {
    return {
      indexAxis: "y",
      maintainAspectRatio: false,
      plugins: {
        legend: {
          labels: {
            color: colors.slate["500"](0.8),
          },
        },
      },
      scales: {
        x: {
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
          },
          grid: {
            color: darkMode ? colors.slate["500"](0.3) : colors.slate["400"](),
            borderDash: [2, 2],
            drawBorder: false,
          },
        },
      },
    };
  });

  return (
    <Chart
      type="bar"
      width={props.width}
      height={props.height}
      data={data}
      options={options}
      className={props.className}
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
