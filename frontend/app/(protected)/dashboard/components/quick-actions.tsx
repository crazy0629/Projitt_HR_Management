import { BanknoteArrowDown, CalendarClock, FolderSearch, MessageCirclePlus, Star, UserRoundPlus } from "lucide-react"

export default function QuickActions() {
    return (
        <div className="w-full rounded-[12px] border border-[#e9e9e9] bg-white p-[16px]">
            <div className="flex gap-[4px]">
                <p className="text-[#4b4b4b] text-[13px]/[16px]">Quick Actions</p>
            </div>
            <div className="flex gap-y-[40px] flex-wrap mt-[24px]">
                <button className="w-[120px] flex flex-col justify-center items-center gap-[4px]">
                    <div className="w-[40px] h-[40px] rounded-[8px] bg-[#0d978b] flex items-center justify-center">
                        <FolderSearch className="size-[20px] text-white" />
                    </div>
                    <p className="text-[#4b4b4b] text-[13px]/[16px]">Post New Job</p>
                </button>
                <button className="w-[120px] flex flex-col justify-center items-center gap-[4px]">
                    <div className="w-[40px] h-[40px] rounded-[8px] bg-[#0d978b] flex items-center justify-center">
                        <UserRoundPlus className="size-[20px] text-white" />
                    </div>
                    <p className="text-[#4b4b4b] text-[13px]/[16px]">Add Employee</p>
                </button>
                <button className="w-[120px] flex flex-col justify-center items-center gap-[4px]">
                    <div className="w-[40px] h-[40px] rounded-[8px] bg-[#0d978b] flex items-center justify-center">
                        <BanknoteArrowDown className="size-[20px] text-white" />
                    </div>
                    <p className="text-[#4b4b4b] text-[13px]/[16px]">Run Payroll</p>
                </button>
                <button className="w-[120px] flex flex-col justify-center items-center gap-[4px]">
                    <div className="w-[40px] h-[40px] rounded-[8px] bg-[#0d978b] flex items-center justify-center">
                        <MessageCirclePlus className="size-[20px] text-white" />
                    </div>
                    <p className="text-[#4b4b4b] text-[13px]/[16px]">Send Message</p>
                </button>
                <button className="w-[120px] flex flex-col justify-center items-center gap-[4px]">
                    <div className="w-[40px] h-[40px] rounded-[8px] bg-[#0d978b] flex items-center justify-center">
                        <UserRoundPlus className="size-[20px] text-white" />
                    </div>
                    <p className="text-[#4b4b4b] text-[13px]/[16px]">Manage Roles</p>
                </button>
                <button className="w-[120px] flex flex-col justify-center items-center gap-[4px]">
                    <div className="w-[40px] h-[40px] rounded-[8px] bg-[#0d978b] flex items-center justify-center">
                        <CalendarClock className="size-[20px] text-white" />
                    </div>
                    <p className="text-[#4b4b4b] text-[13px]/[16px]">Leave Requests</p>
                </button>
                <button className="w-[120px] flex flex-col justify-center items-center gap-[4px]">
                    <div className="w-[40px] h-[40px] rounded-[8px] bg-[#0d978b] flex items-center justify-center">
                        <Star className="size-[20px] text-white" />
                    </div>
                    <p className="text-[#4b4b4b] text-[13px]/[16px]">Star Review Cycle</p>
                </button>
            </div>
        </div>
    )
}