import { ArrowUpRight, Briefcase, MapPinned } from "lucide-react"

export default function OpenJobs() {
    return (
        <div className="w-full rounded-[12px] border border-[#e9e9e9] bg-white p-[16px]">
            <div className="flex justify-between items-center">
                <div className="flex gap-[4px]">
                    <Briefcase className="size-[16px] text-[#4b4b4b]" />
                    <p className="text-[#4b4b4b] text-[13px]/[16px]">Open Jobs (25)</p>
                </div>
                <button><ArrowUpRight className="size-[16px] text-[#1c1c1c]" /></button>
            </div>
            <div className="flex flex-col gap-[12px]  mt-[16px]">
                {new Array(4).fill(0).map((_, index) => <div key={index} className={`flex justify-between items-center border-b border-[#e9e9e9]  ${index === 3 ? 'border-b-0 pb-0' : 'pb-[12px]'}`}>
                    <div>
                        <p className="text-[12px]/[20px]">Senior Data Analyst</p>
                        <div className="flex gap-[8px]">
                            <div className="flex items-center gap-[4px]">
                                <Briefcase className="size-[16px] text-[#626262]" />
                                <p className="text-[10px]/[16px] text-[#787878]">Fulltime</p>
                            </div>
                            <div className="flex items-center gap-[4px]">
                                <MapPinned className="size-[16px] text-[#626262]" />
                                <p className="text-[10px]/[16px] text-[#787878]">United States</p>
                            </div>
                        </div>
                    </div>
                    <button className="text-[10px]/[16px] text-[#0d978b] underline">25 Applicants</button>
                </div>)}
            </div>
        </div>
    )
}