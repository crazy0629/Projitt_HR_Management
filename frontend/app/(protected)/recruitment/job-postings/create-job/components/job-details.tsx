'use client';

/**
 * JobDetails component allows HR or recruiters to define role-specific information for a job post.
 * Includes fields for title, department, type, skills, location, salary, and deadline.
 * Features dynamic validation and adaptable UI for different location types.
 */

import { Switch } from "@/components/ui/switch";
import { Input } from "@/components/ui/input";
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue
} from "@/components/ui/select";
import {
    RadioGroupItem,
    RadioGroup
} from "@/components/ui/radio-group";
import { Label } from "@/components/ui/label";
import { JSX, useEffect, useState } from "react";
import {
    Popover,
    PopoverContent,
    PopoverTrigger
} from "@/components/ui/popover";
import { Button } from "@/components/ui/button";
import { CalendarDays } from "lucide-react";
import { cn } from "@/lib/utils";
import { Calendar } from "@/components/ui/calendar";
import moment from "moment";
import TagInput from "@/components/ui/tag-input";
import SuggestionInput from "@/components/ui/suggestion-input";
import { useBasic } from "@/context/BasicContext";
import { errorHandlers } from "@/utils/error-handler";

interface JobDetailsProps {
    jobData: any;
    setJobData: any;
    errors?: {
        title?: string;
        department_id?: string;
        employment_type_id?: string;
        no_of_job_opening?: string;
        skill_ids?: string;
        location_type_id?: number;
        state?: string;
        country_id?: string;
    };
    triggerValidation?: boolean;
}

/**
 * JobDetails component definition
 * @param jobData - job post data object
 * @param setJobData - setter function for job data
 * @param errors - optional validation error object
 * @param triggerValidation - optional flag to enable error messages
 * @returns JSX.Element
 */
export default function JobDetails({ jobData, setJobData, errors = {}, triggerValidation = false }: JobDetailsProps): JSX.Element {
    const [locationType, setLocationType] = useState(jobData.location_type_id || 1);
    const [date, setDate] = useState<Date | undefined>(new Date());
    const [selectedCountryName, setSelectedCountryName] = useState<string>('');
    const [countryStates, setCountryStates] = useState<string[]>([]);
    const [isLoadingStates, setIsLoadingStates] = useState(false);
    const { country } = useBasic();
    const { department } = useBasic();
    const { employmentType } = useBasic();
    const { skills } = useBasic();
    const { designation } = useBasic();

    // Helper function to get country name from country ID
    const getCountryNameById = (countryId: string): string => {
        const foundCountry = (country as any[] || []).find((c: any) => c.id.toString() === countryId);
        return foundCountry ? foundCountry.name : '';
    };

    useEffect(() => {
        console.log(locationType);

        setJobData({ ...jobData, location_type_id: locationType });
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [locationType]);

    // Update selected country name and fetch states when country changes
    useEffect(() => {
        if (jobData.country_id) {
            const countryName = getCountryNameById(jobData?.country_id?.toString() || '');
            console.log(countryName);
            setSelectedCountryName(countryName);

            // Reset state selection when country changes
            // setJobData({ ...jobData, state: '' });

            // Fetch states for the selected country
            if (countryName) {
                setIsLoadingStates(true);
                fetch('https://countriesnow.space/api/v0.1/countries/states', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ country: countryName })
                })
                    .then(res => res.json())
                    .then(data => {
                        console.log(data);

                        if (data?.data?.states) {
                            setCountryStates(data.data.states);

                            // Check if current jobData.state exists in the fetched states
                            if (jobData.state) {
                                const stateExists = data.data.states.some((state: any) =>
                                    state.name === jobData.state || state.state_code === jobData.state
                                );

                                if (!stateExists) {
                                    // If state doesn't exist in the list, clear it
                                    setJobData({ ...jobData, state: '' });
                                }
                            }
                        }
                    })
                    .catch(error => {
                        errorHandlers.custom(error, 'Error fetching states');
                        setCountryStates([]);
                    })
                    .finally(() => {
                        setIsLoadingStates(false);
                    });
            }
        }
    }, [jobData.country_id, country]);

    return (
        <div>
            <div className="flex justify-between">
                <h1
                    className="text-[20px]/[30px] font-semibold text-[#353535]"
                    id="job-details-title"
                    data-testid="job-details-title"
                >
                    Job Details
                </h1>
                <div className="flex items-center gap-[8px]">
                    <Switch shape="square" size="md" id="job-details-ai-toggle" data-testid="job-details-ai-toggle" />
                    <p className="text-[14px]/[16px] text-[#1c1c1c]">AI Scoring</p>
                </div>
            </div>
            <div className="grid gap-[48px] xl:grid-cols-2 grid-cols-1 mt-[33px]">

                {/* Each input section will be upgraded with ID + data-testid for automation */}

                <div className="flex flex-col gap-[12px]">
                    <p className="text-[14px]/[16px] text-[#1c1c1c]">Job Title *</p>
                    <SuggestionInput
                        placeholder="e.g Sales Representative"
                        value={jobData?.title || ""}
                        onChange={val => setJobData({ ...jobData, title: val })}
                        suggestions={designation}
                    />
                    {triggerValidation && errors.title && (
                        <span className="text-red-500 text-xs">{errors.title}</span>
                    )}
                </div>

                <div className="flex flex-col gap-[12px]">
                    <p className="text-[14px]/[16px] text-[#1c1c1c]">Department *</p>
                    <Select
                        key={`department-${jobData?.department_id}`}
                        defaultValue="sales"
                        value={jobData?.department_id?.toString() || ""}
                        onValueChange={(e) => setJobData({ ...jobData, department_id: e })}
                    >
                        <SelectTrigger
                            className="h-[48px]"
                            id="job-details-department"
                            data-testid="job-details-department"
                        >
                            <SelectValue placeholder="Select a department" />
                        </SelectTrigger>
                        <SelectContent>
                            {(department || [])?.map((department: any) => (
                                <SelectItem key={department.id} value={department.id.toString()}>{department.name}</SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                    {triggerValidation && errors.department_id && <span className="text-red-500 text-xs ">{errors.department_id}</span>}
                </div>
                <div className="flex flex-col gap-[12px]">
                    <p className="text-[14px]/[16px] text-[#1c1c1c]">Employment Type*</p>
                    <Select key={`employment-${jobData?.employment_type_id}`} defaultValue="full-time" value={jobData?.employment_type_id?.toString() || ""} onValueChange={(e) => setJobData({ ...jobData, employment_type_id: e })}>
                        <SelectTrigger className="h-[48px]">
                            <SelectValue placeholder="Select a department" />
                        </SelectTrigger>
                        <SelectContent>
                            {(employmentType || [])?.map((employmentType: any) => (
                                <SelectItem key={employmentType.id} value={employmentType.id.toString()}>{employmentType.name}</SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                    {triggerValidation && errors.employment_type_id && <span className="text-red-500 text-xs ">{errors.employment_type_id}</span>}
                </div>
                <div className="flex flex-col gap-[12px]">
                    <p className="text-[14px]/[16px] text-[#1c1c1c]">No. of Openings*</p>
                    <Select defaultValue="1" value={jobData?.no_of_job_opening} onValueChange={(e) => setJobData({ ...jobData, no_of_job_opening: e })}>
                        <SelectTrigger className="h-[48px]">
                            <SelectValue placeholder="Select a department" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="1">1</SelectItem>
                            <SelectItem value="2">2</SelectItem>
                            <SelectItem value="3">3</SelectItem>
                        </SelectContent>
                    </Select>
                    {triggerValidation && errors.no_of_job_opening && <span className="text-red-500 text-xs ">{errors.no_of_job_opening}</span>}
                </div>
                <div className="flex flex-col gap-[12px]">
                    <div>
                        <p className="text-[14px]/[16px] text-[#1c1c1c]">Skills*</p>
                        <p className="text-[12px]/[20px] text-[#8f8f8f] mt-[4px]">Add 3–6 skills to help AI match stronger applicants. </p>
                    </div>
                    <TagInput tags={jobData?.skill_ids} setTags={(tags) => setJobData({ ...jobData, skill_ids: tags })} suggestions={skills} restrictToSuggestions={true} />
                    {triggerValidation && errors.skill_ids && <span className="text-red-500 text-xs ">{errors.skill_ids}</span>}
                </div>
                <div className="flex flex-col gap-[12px]">
                    <div>
                        <p className="text-[14px]/[16px] text-[#1c1c1c]">Location Type*</p>
                        <p className="text-[12px]/[20px] text-[#8f8f8f] mt-[4px]">Choose based on where the role is performed not where the company is based.</p>
                    </div>
                    <div>
                        <RadioGroup className="flex gap-[32px]" defaultValue={locationType.toString()} onValueChange={(e) => setLocationType(Number(e))}>
                            <div className="flex gap-[6px]">
                                <RadioGroupItem value="1" id="onsite" />
                                <Label htmlFor="onsite" variant="secondary" className={'text-[14px]/[20px] ' + (locationType === 1 ? 'text-[#0d978b]' : '')}>
                                    Onsite
                                </Label>
                            </div>
                            <div className="flex gap-[6px]">
                                <RadioGroupItem value="2" id="hybrid" />
                                <Label htmlFor="hybrid" variant="secondary" className={'text-[14px]/[20px] ' + (locationType === 2 ? 'text-[#0d978b]' : '')}>
                                    Hybrid
                                </Label>
                            </div>
                            <div className="flex gap-[6px]">
                                <RadioGroupItem value="3" id="remote" />
                                <Label htmlFor="remote" variant="secondary" className={'text-[14px]/[20px] ' + (locationType === 3 ? 'text-[#0d978b]' : '')}>
                                    Remote
                                </Label>
                            </div>
                        </RadioGroup>
                        {triggerValidation && errors.location_type_id && <span className="text-red-500 text-xs ">{errors.location_type_id}</span>}
                        {(locationType === 1 || locationType === 2) && <div className="flex flex-col gap-[10px] mt-[10px]">
                            <Select value={jobData.country_id?.toString() || ''} onValueChange={val => setJobData({ ...jobData, country_id: val })}>
                                <SelectTrigger className="h-[48px]">
                                    <SelectValue placeholder="Select Country" />
                                </SelectTrigger>
                                <SelectContent>
                                    {(country || [])?.map((country: any) => (
                                        <SelectItem key={country.id} value={country.id.toString()}>{country.name}</SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            {triggerValidation && errors.country_id && <span className="text-red-500 text-xs ">{errors.country_id}</span>}
                            <Select value={jobData.state || ''} onValueChange={val => setJobData({ ...jobData, state: val })}>
                                <SelectTrigger className="h-[48px]">
                                    <SelectValue placeholder={isLoadingStates ? "Loading states..." : "Select State"} />
                                </SelectTrigger>
                                <SelectContent>
                                    {isLoadingStates ? (
                                        <SelectItem value=" " disabled>Loading states...</SelectItem>
                                    ) : countryStates.length > 0 ? (
                                        countryStates.map((state: any, index: number) => (
                                            <SelectItem key={index} value={state.name}>{state.name}</SelectItem> // state_code: GA, NA, IL
                                        ))
                                    ) : (
                                        <SelectItem value=" " disabled>No states available</SelectItem>
                                    )}
                                </SelectContent>
                            </Select>
                            {triggerValidation && errors.state && <span className="text-red-500 text-xs ">{errors.state}</span>}
                        </div>}
                        {locationType === 3 && <div className="flex flex-col gap-[12px] mt-[10px]">
                            <Select value={jobData.country_id?.toString() || ''} onValueChange={val => setJobData({ ...jobData, country_id: val })}>
                                <SelectTrigger className="h-[48px]">
                                    <SelectValue placeholder="Select Country" />
                                </SelectTrigger>
                                <SelectContent>
                                    {(country || [])?.map((country: any) => (
                                        <SelectItem key={country.id} value={country.id.toString()}>{country.name}</SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            {triggerValidation && errors.country_id && <span className="text-red-500 text-xs ">{errors.country_id}</span>}
                            <div className="flex items-center gap-[8px]">
                                <input type="checkbox" placeholder="Remote (Global)" className="h-[14px] accent-[#0d978b]" />
                                <p className="text-[14px]/[20px] text-[#4b4b4b]">Remote (Global)</p>
                            </div>
                        </div>}
                    </div>
                </div>
                <div className="flex flex-col gap-[12px]">
                    <div>
                        <p className="text-[14px]/[16px] text-[#1c1c1c]">Salary <span className="text-[#787878]">(optional)</span></p>
                        <p className="text-[12px]/[20px] text-[#8f8f8f] mt-[4px]">Adding a range boosts visibility by up to 40%. If unsure, give your best estimate. </p>
                    </div>
                    <Input className="h-[48px]" placeholder="e.g $150,000-$200,000 p.a" value={jobData?.salary} onChange={(e) => setJobData({ ...jobData, salary: e.target.value })} />
                </div>
                <div className="flex flex-col gap-[12px]">
                    <div>
                        <p className="text-[14px]/[16px] text-[#1c1c1c]">Deadline <span className="text-[#787878]">(optional)</span></p>
                        <p className="text-[12px]/[20px] text-[#8f8f8f] mt-[4px]">Set a deadline to reflect urgency. </p>
                    </div>
                    <Popover>
                        <PopoverTrigger asChild>
                            <Button
                                mode="input"
                                variant="outline"
                                id="date"
                                className={cn(
                                    'w-full h-[48px] data-[state=open]:border-primary',
                                    !date && 'text-muted-foreground',
                                )}
                            >
                                <CalendarDays className="-ms-0.5" />
                                {jobData?.deadline ? moment(jobData?.deadline).format('DD/MM/YYYY') : <span>Pick a date</span>}
                            </Button>
                        </PopoverTrigger>
                        <PopoverContent className="w-auto p-0" align="start">
                            <Calendar
                                initialFocus
                                mode="single" // Single date selection
                                defaultMonth={jobData?.deadline}
                                selected={jobData?.deadline}
                                onSelect={(e) => setJobData({ ...jobData, deadline: e })}
                                numberOfMonths={1}
                            />
                        </PopoverContent>
                    </Popover>
                </div>
            </div>
        </div >
    );
}