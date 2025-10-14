import { ArrowUpRight, Briefcase, MapPinned, Calendar } from "lucide-react"

export default function UpcommingInterviews() {
    return (
        <div className="w-full rounded-[12px] border border-[#e9e9e9] bg-white p-[16px]">
            <div className="flex justify-between items-center">
                <div className="flex gap-[4px]">
                    <Calendar className="size-[16px] text-[#4b4b4b]" />
                    <p className="text-[#4b4b4b] text-[13px]/[16px]">Upcomming Interviews (25)</p>
                </div>
                <button><ArrowUpRight className="size-[16px] text-[#1c1c1c]" /></button>
            </div>
            <div className="flex flex-col gap-[12px]  mt-[16px]">
                {new Array(4).fill(0).map((_, index) => <div key={index} className={`w-full border-b border-[#e9e9e9]  ${index === 3 ? 'border-b-0 pb-0' : 'pb-[12px]'}`}>
                    <div>
                        <p className="text-[12px]/[20px] font-medium text-[#353535]">Test Interview</p>
                        <p className="text-[12px]/[20px] text-[#787878] mt-[2px]">20 Nov, 4:00pm with Alice Fernadez</p>
                    </div>
                </div>)}
            </div>
        </div>
    )
}