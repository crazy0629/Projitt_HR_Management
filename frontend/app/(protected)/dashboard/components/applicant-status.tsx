import { User } from "lucide-react"

export default function ApplicantStatus() {
    return (
        <div className="w-full rounded-[12px] border border-[#e9e9e9] bg-white p-[16px]">
            <div className="flex gap-[4px]">
                <User className="size-[16px] text-[#4b4b4b]" />
                <p className="text-[#4b4b4b] text-[13px]/[16px]">Applicant Status</p>
            </div>
            <div className="flex w-full h-[16px] gap-[1px]  mt-[17px]">
                <div className="w-[60%] h-full bg-[#0d978b]"></div>
                <div className="w-[20%] h-full bg-[#ffa750]"></div>
                <div className="w-[15%] h-full bg-[#c30606]"></div>
                <div className="w-[5%] h-full bg-[#00d47d]"></div>
            </div>
            <div className="mt-[26px] flex flex-col gap-[16px]">
                <div className="flex items-center justify-between pb-[16px] border-b border-[#e9e9e9]">
                    <div className="flex gap-[9px] items-center">
                        <div className="w-[12px] h-[12px] bg-[#0d978b]"></div>
                        <p className="text-[12px]/[16px] text-[#353535]">New</p>
                    </div>
                    <p className="text-[12px]/[16px] text-[#353535]">156</p>
                </div>
                <div className="flex items-center justify-between pb-[16px] border-b border-[#e9e9e9]">
                    <div className="flex gap-[9px] items-center">
                        <div className="w-[12px] h-[12px] bg-[#ffa750]"></div>
                        <p className="text-[12px]/[16px] text-[#353535]">Interview</p>
                    </div>
                    <p className="text-[12px]/[16px] text-[#353535]">41</p>
                </div>
                <div className="flex items-center justify-between pb-[16px] border-b border-[#e9e9e9]">
                    <div className="flex gap-[9px] items-center">
                        <div className="w-[12px] h-[12px] bg-[#c30606]"></div>
                        <p className="text-[12px]/[16px] text-[#353535]">Rejected</p>
                    </div>
                    <p className="text-[12px]/[16px] text-[#353535]">29</p>
                </div>
                <div className="flex items-center justify-between">
                    <div className="flex gap-[9px] items-center">
                        <div className="w-[12px] h-[12px] bg-[#00d47d]"></div>
                        <p className="text-[12px]/[16px] text-[#353535]">Hired</p>
                    </div>
                    <p className="text-[12px]/[16px] text-[#353535]">156</p>
                </div>
            </div>
        </div>
    )
}