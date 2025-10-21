import { Popover, PopoverContent, PopoverTrigger } from "@/components/ui/popover";
import { Button } from "@/components/ui/button";
import { ChevronDown, Filter } from "lucide-react";
import { Badge } from "@/components/ui/badge";
import { Checkbox } from "@/components/ui/checkbox";
import { Label } from "@/components/ui/label";
import { JSX, useState } from "react";

/**
 * FilterTool component
 *
 * This component renders a filter UI with popover dropdowns for Locations,
 * Departments, Types, and Status filters. Each filter shows the number of
 * selected options or "All" if none selected. User selections are managed
 * with internal state arrays for each category.
 *
 * Returns:
 *  JSX.Element - The rendered filter tool component.
 */
export const AssessmentFilterTool = (): JSX.Element => {
    const [selectedTypes, setSelectedTypes] = useState<string[]>([]);
    const [selectedStatuses, setSelectedStatuses] = useState<string[]>([]);
    const types = ["Full-time", "Part-time", "Contract", "Internship"];
    const statuses = ["Open", "Closed", "Draft"];

    /**
     * Handle type checkbox change
     * @param checked - boolean whether the checkbox is checked
     * @param value - type name
     */
    const handleTypeChange = (checked: boolean, value: string): void => {
        setSelectedTypes((prev = []) =>
            checked ? [...prev, value] : prev.filter((v) => v !== value)
        );
    };

    /**
     * Handle status checkbox change
     * @param checked - boolean whether the checkbox is checked
     * @param value - status name
     */
    const handleStatusChange = (checked: boolean, value: string): void => {
        setSelectedStatuses((prev = []) =>
            checked ? [...prev, value] : prev.filter((v) => v !== value)
        );
    };

    return (
        <div className="flex flex-wrap gap-[11px] mt-[17px]">
            {/* Types filter */}
            <Popover>
                <PopoverTrigger asChild>
                    <div
                        className="cursor-pointer flex gap-[8px] py-[6px] px-[14px] border border-[#d2d2d2] rounded-[8px]"
                        id="filter-types-trigger"
                        data-testid="filter-types-trigger"
                    >
                        <span className="text-[14px]/[20px] text-[#787878]">
                            {selectedTypes.length > 0
                                ? "Types: " + selectedTypes.length + " selected"
                                : "All Types"}
                        </span>
                        <ChevronDown
                            className="size-[18px] text-[#4b4b4b]"
                            id="filter-types-chevron"
                            data-testid="filter-types-chevron"
                        />
                    </div>
                </PopoverTrigger>
                <PopoverContent
                    className="w-40 p-3"
                    align="start"
                    id="filter-types-content"
                    data-testid="filter-types-content"
                >
                    <div className="space-y-3">
                        {types.map((type, index) => (
                            <div
                                key={index}
                                className="flex items-center gap-2.5"
                                id={`filter-type-item-${index}`}
                                data-testid={`filter-type-item-${index}`}
                            >
                                <Checkbox
                                    id={`filter-type-checkbox-${index}`}
                                    checked={selectedTypes.includes(type)}
                                    onCheckedChange={(checked) =>
                                        handleTypeChange(checked === true, type)
                                    }
                                    data-testid={`filter-type-checkbox-${index}`}
                                />
                                <Label
                                    htmlFor={`filter-type-checkbox-${index}`}
                                    className="grow flex items-center justify-between font-normal gap-1.5"
                                >
                                    {type}
                                </Label>
                            </div>
                        ))}
                    </div>
                </PopoverContent>
            </Popover>

            {/* Status filter */}
            <Popover>
                <PopoverTrigger asChild>
                    <div
                        className="cursor-pointer flex gap-[8px] py-[6px] px-[14px] border border-[#d2d2d2] rounded-[8px]"
                        id="filter-status-trigger"
                        data-testid="filter-status-trigger"
                    >
                        <span className="text-[14px]/[20px] text-[#787878]">
                            {selectedStatuses.length > 0
                                ? "Status: " + selectedStatuses.length + " selected"
                                : "All Status"}
                        </span>
                        <ChevronDown
                            className="size-[18px] text-[#4b4b4b]"
                            id="filter-status-chevron"
                            data-testid="filter-status-chevron"
                        />
                    </div>
                </PopoverTrigger>
                <PopoverContent
                    className="w-40 p-3"
                    align="start"
                    id="filter-status-content"
                    data-testid="filter-status-content"
                >
                    <div className="space-y-3">
                        {statuses.map((status, index) => (
                            <div
                                key={index}
                                className="flex items-center gap-2.5"
                                id={`filter-status-item-${index}`}
                                data-testid={`filter-status-item-${index}`}
                            >
                                <Checkbox
                                    id={`filter-status-checkbox-${index}`}
                                    checked={selectedStatuses.includes(status)}
                                    onCheckedChange={(checked) =>
                                        handleStatusChange(checked === true, status)
                                    }
                                    data-testid={`filter-status-checkbox-${index}`}
                                />
                                <Label
                                    htmlFor={`filter-status-checkbox-${index}`}
                                    className="grow flex items-center justify-between font-normal gap-1.5"
                                >
                                    {status}
                                </Label>
                            </div>
                        ))}
                    </div>
                </PopoverContent>
            </Popover>
        </div>
    );
};
