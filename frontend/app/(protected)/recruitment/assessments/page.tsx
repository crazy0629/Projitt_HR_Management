"use client";

import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Code, EllipsisVertical, ListFilter, Search, X } from "lucide-react";
import { useRouter } from "next/navigation";
import { JSX, useEffect, useState } from "react";
import { AssessmentFilterTool } from "./components/filter";
import { NoData } from "./components/noData";
import { DropdownMenu, DropdownMenuContent, DropdownMenuTrigger } from "@/components/ui/dropdown-menu";
import CheckDialog from "../job-postings/components/checkDialog";

/**
 * Interface representing each assessment/job item.
 */
interface IData {
    id: string;
    title: string;
    description: string;
    status: string;
    type: string;
}

// Mock job data for assessments
const jobData: IData[] = [
    { id: '1', title: 'JS Array Drill', description: 'Evaluates alignment with company values and logical skills.', status: 'Open', type: 'Coding Challenge' },
    { id: '2', title: 'Culture Fit + Reasoning', description: 'Evaluates alignment with company values and logical skills.', status: 'Open', type: 'Coding Challenge' },
    { id: '3', title: 'Python Code Test', description: 'Evaluates alignment with company values and logical skills.', status: 'Open', type: 'Coding Challenge' },
    { id: '4', title: 'Culture Fit + Reasoning', description: 'Evaluates alignment with company values and logical skills.', status: 'Open', type: 'Coding Challenge' },
    { id: '5', title: 'JS Array Drill', description: 'Evaluates alignment with company values and logical skills.', status: 'Draft', type: 'Coding Challenge' },
    { id: '6', title: 'JS Array Drill', description: 'Evaluates alignment with company values and logical skills.', status: 'Open', type: 'Coding Challenge' },
    { id: '7', title: 'Python Code Test', description: 'Evaluates alignment with company values and logical skills.', status: 'Open', type: 'Coding Challenge' },
    { id: '8', title: 'Culture Fit + Reasoning', description: 'Evaluates alignment with company values and logical skills.', status: 'Open', type: 'Coding Challenge' },
    { id: '9', title: 'Js Code Test', description: 'Evaluates alignment with company values and logical skills.', status: 'Open', type: 'Coding Challenge' },
    { id: '10', title: 'Java Test', description: 'Evaluates alignment with company values and logical skills.', status: 'Open', type: 'Coding Challenge' },
    { id: '11', title: 'Rust Test', description: 'Evaluates alignment with company values and logical skills.', status: 'Open', type: 'Coding Challenge' },

];

/**
 * Component: Assessment
 * ----------------------
 * Renders the list of assessments, filter/search UI, and actions for each item like edit, delete, close, etc.
 */
export default function Assessment() {
    const router = useRouter();
    const [searchQuery, setSearchQuery] = useState('');
    const [showFliter, setShowFilter] = useState(false);
    const [filteredData, setFilteredData] = useState<IData[]>([]);

    useEffect(() => {
        setFilteredData(
            jobData.filter((item) =>
                item.title.toLowerCase().includes(searchQuery.toLowerCase())
            )
        );
    }, [searchQuery]);

    /**
     * Component: ActionsCell
     * ----------------------
     * Renders dropdown action items for each assessment row.
     */
    function ActionsCell({ row }: { row: string }): JSX.Element {
        return (
            <DropdownMenu>
                <DropdownMenuTrigger asChild>
                    <Button
                        id={`actions-button-${row}`}
                        className="size-7"
                        mode="icon"
                        variant="ghost"
                        data-testid={`actions-button-${row}`}
                    >
                        <EllipsisVertical />
                    </Button>
                </DropdownMenuTrigger>
                <DropdownMenuContent
                    id={`actions-menu-${row}`}
                    side="bottom"
                    align="end"
                    data-testid={`actions-menu-${row}`}
                >
                    {filteredData.find((item) => item.id === row)?.status === "Draft" &&
                        <div
                            id={`edit-action-${row}`}
                            className="cursor-pointer hover:bg-[#e9e9e9] text-[12px]/[18px] py-[7px] px-[12px] rounded-[8px]"
                            data-testid={`edit-action-${row}`}
                        >
                            Edit
                        </div>
                    }
                    <div
                        id={`view-applicants-action-${row}`}
                        className="cursor-pointer hover:bg-[#e9e9e9] text-[12px]/[18px] py-[7px] px-[12px] rounded-[8px]"
                        data-testid={`view-applicants-action-${row}`}
                    >
                        View Applicants
                    </div>
                    <div
                        id={`duplicate-action-${row}`}
                        className="cursor-pointer hover:bg-[#e9e9e9] text-[12px]/[18px] py-[7px] px-[12px] rounded-[8px]"
                        data-testid={`duplicate-action-${row}`}
                    >
                        Duplicate
                    </div>
                    {filteredData.find((item) => item.id === row)?.status === "Open" &&
                        <CheckDialog
                            action="close"
                            trigger={
                                <div
                                    id={`close-job-action-${row}`}
                                    className="cursor-pointer hover:bg-[#e9e9e9] text-[12px]/[18px] py-[7px] px-[12px] rounded-[8px]"
                                    data-testid={`close-job-action-${row}`}
                                >
                                    Close Assessment
                                </div>
                            }
                        />
                    }
                    {filteredData.find((item) => item.id === row)?.status === "Open" &&
                        <CheckDialog
                            action="unpublish"
                            trigger={
                                <div
                                    id={`unpublish-action-${row}`}
                                    className="cursor-pointer hover:bg-[#e9e9e9] text-[12px]/[18px] py-[7px] px-[12px] rounded-[8px]"
                                    data-testid={`unpublish-action-${row}`}
                                >
                                    Unpublish
                                </div>
                            }
                        />
                    }
                    <CheckDialog
                        action="delete"
                        trigger={
                            <div
                                id={`delete-action-${row}`}
                                className="cursor-pointer hover:bg-[#e9e9e9] text-[12px]/[18px] py-[7px] px-[12px] rounded-[8px]"
                                data-testid={`delete-action-${row}`}
                            >
                                Delete
                            </div>
                        }
                    />
                </DropdownMenuContent>
            </DropdownMenu>
        );
    }

    return (
        <div className='w-full' data-testid="assessments-container" id="assessments-container">
            <div className='flex items-center justify-between mb-[37px]'>
                <p
                    className='text-[24px]/[30px] font-semibold'
                    data-testid="page-title"
                    id="page-title"
                >
                    Assessment
                </p>
                <Button
                    id="create-assessment-button"
                    className='h-[42px] font-semibold text-[14px]/[20px]'
                    data-testid="create-aseessment-button"
                    onClick={() => { router.push('/recruitment/assessments/create-assessment') }}
                >
                    Create Assessment
                </Button>
            </div>
            <div className="flex items-center justify-between">
                <div className="relative" id="search-wrapper">
                    <Search
                        className="size-4 text-muted-foreground absolute start-3 top-1/2 -translate-y-1/2"
                        data-testid="search-icon"
                        id="search-icon"
                    />
                    <Input
                        id="search-input"
                        placeholder="Search Job"
                        value={searchQuery}
                        onChange={(e) => setSearchQuery(e.target.value)}
                        className="w-[172px] ps-9 h-[32px]"
                        data-testid="search-input"
                    />
                    {searchQuery.length > 0 && (
                        <Button
                            id="clear-search-button"
                            mode="icon"
                            variant="ghost"
                            className="absolute end-1.5 top-1/2 -translate-y-1/2 h-6 w-6"
                            onClick={() => setSearchQuery('')}
                            data-testid="clear-search-button"
                        >
                            <X />
                        </Button>
                    )}
                </div>
                <Button
                    id="filter-button"
                    variant="outline"
                    onClick={() => setShowFilter(!showFliter)}
                    className='text-[#053834] px-[12px] py-[6px] flex items-center gap-[6px] font-semibold'
                    data-testid="filter-button"
                >
                    <ListFilter className='size-[20px]' />
                    Filter
                </Button>
            </div>
            {showFliter && <AssessmentFilterTool />}
            <div className="mt-[28px]">
                {filteredData.length === 0 &&
                    <NoData data-testid="no-data-message" />
                }
                {filteredData.length > 0 &&
                    <div
                        className='w-full grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-[32px] h-[calc(100vh-300px)] overflow-y-auto'
                        data-testid="grid-view-container"
                        id="grid-view-container"
                    >
                        {filteredData.map((item) => (
                            <div
                                key={item.id}
                                className="w-full bg-white flex flex-col h-auto px-[20px] py-[24px] border border-[#e9e9e9] rounded-[16px]"
                                data-testid={`job-card-${item.id}`}
                                id={`job-card-${item.id}`}
                            >
                                <div className='w-full flex justify-between'>
                                    <p className="text-[16px]/[24px] font-medium">{item.title}</p>
                                    <div className='flex gap-[14px]'>
                                        <ActionsCell row={item.id} />
                                    </div>
                                </div>
                                <p className="text-[14px]/[22px] mt-[8px] text-[#626262]">{item.description}</p>
                                <div className="mt-[12px] flex justify-between">
                                    <div className={`rounded-[100px] px-[8px] py-[2px] flex gap-[4px] items-center ${item.status === "Open" ? "bg-[#d6eeec] text-[#0d978b]" : "bg-[#e9e9e9] text-[#626262]"}`}>
                                        <Code className="size-[16px]" />
                                        <p className="text-[14px]/[22px]">{item.type}</p>
                                    </div>
                                    {item.status === "Draft" && <p className="text-[12px]/[20px] text-[#626262]">{item.status}</p>}
                                </div>
                            </div>
                        ))}
                    </div>
                }
            </div>
        </div>
    );
}
