import { ArrowUpRight } from "lucide-react"

export default function YourPlan() {
    return (
        <div className="w-full rounded-[12px] border border-[#e9e9e9] bg-white p-[16px]">
            <div className="flex justify-between items-center">
                <div className="flex gap-[4px]">
                    <p className="text-[#4b4b4b] text-[13px]/[16px]">Your Plan</p>
                </div>
                <button><ArrowUpRight className="size-[16px] text-[#1c1c1c]" /></button>
            </div>
            <div className="mt-[16px]">
                <div className="p-[12px] rounded-[8px] bg-[#d6eeec] flex justify-between items-center">
                    <p className="text-[14px]/[22px] font-medium text-[#1c1c1c]">Projitt Business</p>
                    <button className="text-[12px]/[20px] text-[#0d978b]">Upgrade Plan</button>
                </div>
                <div className="mt-[20px] grid md:grid-cols-2 grid-cols-1 gap-y-[20px]">
                    <div className="flex gap-[8px] items-center">
                        <div className="w-[12px] h-[12px] rounded-[0.75px] bg-[#00D47D]"></div>
                        <p className="text-[12px]/[16px]text-[#353535]">Talent Management</p>
                    </div>
                    <div className="flex gap-[8px] items-center">
                        <div className="w-[12px] h-[12px] rounded-[0.75px] bg-[#c30606]"></div>
                        <p className="text-[12px]/[16px]text-[#353535]">Leave & Attendance</p>
                    </div>
                    <div className="flex gap-[8px] items-center">
                        <div className="w-[12px] h-[12px] rounded-[0.75px] bg-[#00D47D]"></div>
                        <p className="text-[12px]/[16px]text-[#353535]">Payroll & Tax</p>
                    </div>
                    <div className="flex gap-[8px] items-center">
                        <div className="w-[12px] h-[12px] rounded-[0.75px] bg-[#c30606]"></div>
                        <p className="text-[12px]/[16px]text-[#353535]">Benefits</p>
                    </div>
                    <div className="flex gap-[8px] items-center">
                        <div className="w-[12px] h-[12px] rounded-[0.75px] bg-[#00D47D]"></div>
                        <p className="text-[12px]/[16px]text-[#353535]">Recruitement</p>
                    </div>
                </div>
            </div>
        </div >
    )
}