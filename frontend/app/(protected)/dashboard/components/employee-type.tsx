import { UsersRound } from "lucide-react"
import Chart from "react-apexcharts";
import ApexCharts from "apexcharts";

export default function EmployeeType() {
    const totalEmployees = 180;
    const fulltime = 156;
    const freelance = 24;

    const options: ApexCharts.ApexOptions = {
        chart: {
            type: "donut",
        },
        labels: ["Fulltime", "Freelance"],
        colors: ["#18988B", "#FFA750"],
        legend: {
            show: false,
        },
        plotOptions: {
            pie: {
                startAngle: -135,
                endAngle: 135,
                donut: {
                    size: "80%",
                },
            },
        },
        dataLabels: {
            enabled: false,
        },
        stroke: {
            width: 0,
        },
    };

    const series = [fulltime, freelance];
    return (
        <div className="w-full rounded-[12px] border border-[#e9e9e9] bg-white p-[16px]">
            <div className="flex justify-between items-center">
                <div className="flex gap-[4px]">
                    <UsersRound className="size-[16px] text-[#4b4b4b]" />
                    <p className="text-[#4b4b4b] text-[13px]/[16px]">Employee Type</p>
                </div>

            </div>
            <div className="flex flex-col items-center mt-[28px]">
                <div style={{ width: "200px" }}>
                    <Chart options={options} series={series} type="donut" />
                    <div className="text-center -mt-[115px]">
                        <h2 className="text-[16px]/[21px] font-bold text-[#0a0a0a]">{totalEmployees}</h2>
                        <p className="text-[9px]/[14px] font-medium text-[#737373] mt-[5px]">Total Employees</p>
                    </div>
                </div>

                <div className="flex justify-center gap-[34px] mt-[63px]">
                    <div className="flex items-center gap-[4px]">
                        <div className="w-[12px] h-[12px] rounded-[0.75px] bg-[#18988B]"></div>
                        <span className="text-[12px]/[16px] font-medium text-[#737373]">{fulltime} Fulltime</span>
                    </div>
                    <div className="flex items-center gap-[4px]">
                        <div className="w-[12px] h-[12px] rounded-[0.75px] bg-[#FFA750]"></div>
                        <span className="text-[12px]/[16px] font-medium text-[#737373]">{freelance} Freelance</span>
                    </div>
                </div>
            </div>
        </div>
    )
}