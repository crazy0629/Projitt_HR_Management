import { ArrowRight, Banknote, Calendar, Star, User } from "lucide-react"

export default function ActionList() {
    return (
        <div className="w-full rounded-[12px] border border-[#e9e9e9] bg-white p-[16px]">
            <div className="flex justify-between items-center">
                <div className="flex gap-[4px]">
                    <p className="text-[#4b4b4b] text-[13px]/[16px]">Actions List</p>
                    <span className="w-[16px] h-[16px] rounded-full bg-[#0d978b] text-white flex items-center justify-center text-[8px] font-medium">12</span>
                </div>
            </div>
            <div className="flex flex-col gap-[12px]  mt-[16px]">
                <div className="flex gap-[14px] items-center">
                    <Calendar className="size-[20px] text-[#ffa750] mt-[-12px]" />
                    <div className="flex-1 flex justify-between items-center border-b border-[#e9e9e9] pb-[12px]">
                        <div className="flex flex-col gap-[2px]">
                            <p className="text-[12px]/[16px] font-medium text-[#353535]">Pending Leave Requests</p>
                            <p className="text-[10px]/[16px] font-medium text-[#787878]">6 leave requests awaiting approval</p>
                        </div>
                        <button><ArrowRight className="size-[16px] text-[#0d978b]" /></button>
                    </div>
                </div>
                <div className="flex gap-[14px] items-center">
                    <Banknote className="size-[20px] text-[#00d47d] mt-[-12px]" />
                    <div className="flex-1 flex justify-between items-center border-b border-[#e9e9e9] pb-[12px]">
                        <div className="flex flex-col gap-[2px]">
                            <p className="text-[12px]/[16px] font-medium text-[#353535]">Upcoming Payroll Run</p>
                            <p className="text-[10px]/[16px] font-medium text-[#787878]">Payroll for June 30 is due in 3 days. </p>
                        </div>
                        <button className="text-[12px]/[22px] text-white bg-[#0d978b] rounded-[5px] px-[8px]">Run Payroll</button>
                    </div>
                </div>
                <div className="flex gap-[14px] items-center">
                    <User className="size-[20px] text-[#626262] mt-[-12px]" />
                    <div className="flex-1 flex justify-between items-center border-b border-[#e9e9e9] pb-[12px]">
                        <div className="flex flex-col gap-[2px]">
                            <p className="text-[12px]/[16px] font-medium text-[#353535]">Pending Offer Letter</p>
                            <p className="text-[10px]/[16px] font-medium text-[#787878]">1 candidate marked “Hired” but offer not sent</p>
                        </div>
                        <button><ArrowRight className="size-[16px] text-[#0d978b]" /></button>
                    </div>
                </div>
                <div className="flex gap-[14px] items-center">
                    <Star className="size-[20px] text-[#428bc1]" />
                    <div className="flex-1 flex justify-between items-center">
                        <div className="flex flex-col gap-[2px]">
                            <p className="text-[12px]/[16px] font-medium text-[#353535]">Promotion Request Pending</p>
                            <p className="text-[10px]/[16px] font-medium text-[#787878]">6 leave requests awaiting approval</p>
                        </div>
                        <button><ArrowRight className="size-[16px] text-[#0d978b]" /></button>
                    </div>
                </div>
            </div>
        </div >
    )
}