import { Chart } from "@/base-components";
import { colors } from "@/lib";
import PropTypes from "prop-types";
import { useRecoilValue } from "recoil";
import { colorScheme as colorSchemeStore } from "@/stores/color-scheme";
import { darkMode as darkModeStore } from "@/stores/dark-mode";
import { useMemo } from "react";

function Main(props) {
  const darkMode = useRecoilValue(darkModeStore);
  const colorScheme = useRecoilValue(colorSchemeStore);

  const chartData = [15, 10, 65];
  const backgroundColor = () => [
    colors.pending(0.6),
    colors.warning(0.6),
    colors.primary(0.6),
  ];
  const hoverBackgroundColor = () => [
    colors.pending(0.6),
    colors.warning(0.6),
    colors.primary(0.6),
  ];
  const borderColor = () => [
    colors.pending(0.75),
    colors.warning(0.9),
    colors.primary(0.5),
  ];

  const data = useMemo(() => {
    return {
      labels: ["Html", "Vuejs", "Laravel"],
      datasets: [
        {
          data: chartData,
          backgroundColor: darkMode || colorScheme ? backgroundColor() : "",
          hoverBackgroundColor:
            darkMode || colorScheme ? hoverBackgroundColor() : "",
          borderWidth: 1,
          borderColor: darkMode || colorScheme ? borderColor() : "",
        },
      ],
    };
  });

  const options = useMemo(() => {
    return {
      maintainAspectRatio: false,
      plugins: {
        legend: {
          labels: {
            color: colors.slate["500"](0.8),
          },
        },
      },
      cutout: "80%",
    };
  });

  return (
    <Chart
      type="doughnut"
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
