import Chart from "react-apexcharts";
import ApexCharts from "apexcharts";

export default function EmployeeStatus() {
    const options: ApexCharts.ApexOptions = {
        chart: {
            type: "bar",
            height: 200,
            toolbar: {
                show: false, // Disables the toolbar (menu icon) in the top-right corner
            },
        },
        plotOptions: {
            bar: {
                horizontal: true,
                barHeight: "50%",
                distributed: true, // different colors per bar
            },
        },
        colors: ["#4A90E2", "#18988B", "#B00020", "#FFA750"],
        dataLabels: {
            enabled: false,
        },
        xaxis: {
            categories: ["Onboarding", "Active", "Suspended", "Offboarding"],
            labels: {
                style: {
                    fontSize: "12px",
                },
            },
            tickAmount: 11,
        },
        yaxis: {
            labels: {
                style: {
                    fontSize: "12px",
                },
            },
            show: false,
        },
        grid: {
            show: true, // Show grid lines
            borderColor: "#e0e0e0", // Color of grid lines
            strokeDashArray: 4, // Dashed grid lines (you can adjust this)
            xaxis: {
                lines: {
                    show: true, // Ensure grid lines show for x-axis
                },
            },
            yaxis: {
                lines: {
                    show: false, // Show grid lines for y-axis too
                },
            },
        },
        legend: {
            show: false, // Disable the legend
        },

    };

    const series = [
        {
            data: [12, 90, 18, 8], // Example values
        },
    ];
    return (
        <div className="w-full rounded-[12px] border border-[#e9e9e9] bg-white p-[16px]">
            <div className="flex justify-between items-center">
                <div className="flex gap-[4px]">
                    <p className="text-[#4b4b4b] text-[13px]/[16px]">Employee Status</p>
                </div>

            </div>
            <div className="w-full mt-[16px]">
                <Chart options={options} series={series} type="bar" height={200} />
            </div>
        </div>
    )
}