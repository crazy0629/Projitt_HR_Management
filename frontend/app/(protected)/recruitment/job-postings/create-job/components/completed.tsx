import { Button } from "@/components/ui/button";
import DialogContent, { Dialog, DialogTitle, DialogTrigger } from "@/components/ui/dialog";
import { Tooltip, TooltipContent, TooltipTrigger } from "@/components/ui/tooltip";
import { BriefcaseBusiness, ChevronDown, Clock, Copy, Linkedin, MapPin, PieChart, Plane, Send, Share2, Star, Twitter, Users } from "lucide-react";
import { useState } from "react";

/**
 * Completed Component
 * Displays a confirmation message that the job has been published,
 * along with share options (social and copy link) and view job details.
 */
export default function Completed() {
    const url = "https://www.figma.com/file/NlfVhYygR9mAQasassdsada/Share...";
    const [open, setOpen] = useState(false);

    /**
     * Copies the job share URL to the clipboard and shows tooltip feedback.
     */
    const handleCopy = () => {
        navigator.clipboard.writeText(url).then(() => {
            setOpen(true);
            setTimeout(() => {
                setOpen(false);
            }, 2000);
        }).catch((err) => {
            console.error("Failed to copy: ", err);
        });
    };

    return (
        <div className="w-full flex flex-col items-center" id="completed-root" data-testid="completed-root">
            <div className="flex flex-col items-center">
                <div className="relative mt-[200px]">
                    <div className="sm:w-[286px] w-[250px] h-[250px] flex items-center justify-center absolute rounded-[7px] top-[-40px] sm:left-[-40px] left-[-10px] bg-[linear-gradient(to_right,_#0D978B91,_#FFFFFF80)]">
                        <div className="sm:w-[283px] w-[247px] h-[247px] bg-white rounded-[5px]"></div>
                    </div>
                    <div className="sm:w-[286px] w-[250px] h-[250px] flex items-center justify-center absolute rounded-[7px] top-[-20px] sm:left-[-20px] left-[-5px] bg-[linear-gradient(to_right,_#0D978B91,_#FFFFFF80)]">
                        <div className="sm:w-[283px] w-[247px] h-[247px] bg-white rounded-[5px]"></div>
                    </div>
                    <div
                        className="sm:w-[300px] w-[250px] relative bg-white flex flex-col rounded-[7px] border-[0.85px] border-[#e9e9e9] px-[17px] py-[20px]"
                        id="job-card" data-testid="job-card"
                    >
                        <div className='w-full flex justify-between'>
                            <span className='text-[10px]/[18px] px-[8px] text-[#4B4B4B] bg-[#f9f9f9] rounded-[29px] flex items-center gap-[4px]' id="badge-data" data-testid="badge-data">
                                <PieChart className='text-[#00D47D] size-[13px]' />
                                Data
                            </span>
                        </div>
                        <p className='mt-[8.5px] text-[15px]/[25px] font-semibold' id="job-title" data-testid="job-title">
                            Senior Data Analyst
                        </p>
                        <div className='mt-[3.4px] flex gap-[8px]'>
                            <span className='text-[10px]/[15px] px-[8px] flex items-center gap-[2px] text-[#787878]' id="job-type" data-testid="job-type">
                                <BriefcaseBusiness className='size-[13px]' />
                                Fulltime
                            </span>
                            <span className='text-[12px]/[18px] px-[8px] flex items-center gap-[2px] text-[#787878]' id="job-location" data-testid="job-location">
                                <MapPin className='size-[13px]' />
                                United States
                            </span>
                        </div>

                        <div className='mt-[11px] w-full flex rounded-[5px] border border-[#e9e9e9] pl-[12px] pr-[7px] py-[7px]' id="job-stats" data-testid="job-stats">
                            <div className='w-1/2 border-r border-[#e9e9e9]'>
                                <div className='flex gap-[4px]'>
                                    <Users className='size-[17px] text-[#4b4b4b]' />
                                    <span className='text-[14px]/[19px] font-semibold text-[#4b4b4b]'>-</span>
                                </div>
                                <p className='text-[10px]/[19px] text-[#8f8f8f]'>Candidates Applied</p>
                            </div>
                            <div className='w-1/2 pl-[14px]'>
                                <div className='flex gap-[4px]'>
                                    <Star className='size-[17px] text-[#4b4b4b]' />
                                    <span className='text-[14px]/[19px] font-semibold text-[#4b4b4b]'>-</span>
                                </div>
                                <p className='text-[10px]/[19px] text-[#8f8f8f]'>Shortlisted</p>
                            </div>
                        </div>

                        <div className='mt-[8px] flex gap-[4px]' id="job-date" data-testid="job-date">
                            <Clock className='size-[14px] text-[#8f8f8f]' />
                            <span className='text-[10px]/[16px] text-[#8f8f8f]'>June 30, 2025</span>
                        </div>

                        <div className='mt-[15px] flex justify-between w-full'>
                            <Button
                                className='h-[20px] rounded-full bg-[#0D978B] hover:bg-[#0D978B]'
                                id="job-status-button" data-testid="job-status-button"
                            >
                                <span className='text-[10px]/[19px] text-white'>Open</span>
                                <ChevronDown className='size-[10px] text-white' />
                            </Button>
                        </div>
                    </div>
                </div>

                <p className="text-[22px]/[30px] mt-[56px] font-medium text-[#1c1c1c]" id="confirmation-message" data-testid="confirmation-message">
                    Your job has been published!
                </p>
                <p className="text-[16px]/[22px] font-medium text-[#626262] mt-[12px]" id="subtext-message" data-testid="subtext-message">
                    Applicants can now view and apply for this role. You’ll start receiving applications shortly.
                </p>

                <div className="mt-[20px] flex gap-[16px] justify-center">
                    <Button
                        variant="outline"
                        className="w-[116px] h-[42px] bg-transparent border-[#053834] text-[#053834] font-semibold"
                        id="view-details-button" data-testid="view-details-button"
                    >
                        View Details
                    </Button>

                    <Dialog>
                        <DialogTrigger asChild>
                            <Button
                                className="w-[116px] h-[42px] text-white font-semibold"
                                id="share-button" data-testid="share-button"
                            >
                                <Share2 className="size-[17px]" />
                                Share
                            </Button>
                        </DialogTrigger>

                        <DialogTitle />
                        <DialogContent
                            className="w-[90%] md:w-[392px] pl-0 pr-0 pt-0 pb-0 !rounded-[16px]"
                            id="share-dialog" data-testid="share-dialog"
                        >
                            <div className="flex flex-col gap-[8px] py-[24px] px-[24px]">
                                <p className="text-[16px]/[20px] font-semibold text-[#353535]">Share with</p>
                                <div className="flex w-full justify-between mt-[36px] px-[18px]">
                                    {/* Share options */}
                                    {[
                                        { icon: <Send className="size-[24px] text-[#4b4b4b]" />, label: "Telegram" },
                                        { icon: <Twitter className="size-[24px] text-[#4b4b4b]" />, label: "Twitter" },
                                        { icon: <Linkedin className="size-[24px] text-[#4b4b4b]" />, label: "LinkedIn" },
                                    ].map(({ icon, label }) => (
                                        <div
                                            key={label}
                                            className="flex flex-col items-center gap-[8px] cursor-pointer"
                                            id={`share-${label.toLowerCase()}`} data-testid={`share-${label.toLowerCase()}`}
                                        >
                                            <div className="w-[72px] h-[72px] bg-[#f8f8f8] rounded-full flex items-center justify-center">
                                                {icon}
                                            </div>
                                            <p className="text-[12px]/[15px] text-[#4b4b4b]">{label}</p>
                                        </div>
                                    ))}
                                </div>

                                <p className="text-[12px]/[15px] text-[#787878] font-semibold mt-[46px] text-center">
                                    Or share with link
                                </p>

                                <div
                                    className="flex w-full justify-between items-center gap-[8px] bg-[#f8f8f8] rounded-[16px] px-[17px] py-[14px]"
                                    id="share-link-container" data-testid="share-link-container"
                                >
                                    <p className="w-[calc(100%-20px)] overflow-ellipsis overflow-hidden text-[14px]/[17.5px] text-[#8f8f8f]" id="share-url" data-testid="share-url">
                                        {url}
                                    </p>
                                    <Tooltip open={open}>
                                        <TooltipTrigger>
                                            <Copy
                                                className="text-[#0d978b] size-[24px] cursor-pointer"
                                                onClick={handleCopy}
                                                id="copy-button" data-testid="copy-button"
                                            />
                                        </TooltipTrigger>
                                        <TooltipContent>
                                            Copy
                                        </TooltipContent>
                                    </Tooltip>
                                </div>
                            </div>
                        </DialogContent>
                    </Dialog>
                </div>
            </div>
        </div>
    );
}
