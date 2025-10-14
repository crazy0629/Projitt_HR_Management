import * as React from "react";
import { Check, ChevronsUpDown, Plus } from "lucide-react";
import { cn } from "@/lib/utils";
import { Button } from "@/components/ui/button";
import {
    Command,
    CommandEmpty,
    CommandGroup,
    CommandInput,
    CommandItem,
    CommandList,
} from "@/components/ui/command";
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from "@/components/ui/popover";
import { Input } from "@/components/ui/input";

interface EditableSelectProps {
    value: string;
    onValueChange: (value: string) => void;
    options: { value: string; label: string }[];
    placeholder?: string;
    searchPlaceholder?: string;
    emptyText?: string;
    className?: string;
    disabled?: boolean;
    allowCustom?: boolean;
    customPlaceholder?: string;
    dataTestId?: string;
}

export function EditableSelect({
    value,
    onValueChange,
    options,
    placeholder = "Select an option...",
    searchPlaceholder = "Search options...",
    emptyText = "No options found.",
    className,
    disabled = false,
    allowCustom = true,
    customPlaceholder = "Enter custom value...",
    dataTestId,
}: EditableSelectProps) {
    const [open, setOpen] = React.useState(false);
    const [isCustomMode, setIsCustomMode] = React.useState(false);
    const [customValue, setCustomValue] = React.useState("");
    const [searchValue, setSearchValue] = React.useState("");

    // Check if current value is custom (not in options)
    React.useEffect(() => {
        const isCustom = value && !options.find(option => option.value === value);
        setIsCustomMode(isCustom);
        if (isCustom) {
            setCustomValue(value);
        }
    }, [value, options]);

    const handleCustomSubmit = () => {
        if (customValue.trim()) {
            onValueChange(customValue.trim());
            setOpen(false);
            setIsCustomMode(false);
            setCustomValue("");
        }
    };

    const handleKeyDown = (e: React.KeyboardEvent) => {
        if (e.key === "Enter") {
            e.preventDefault();
            handleCustomSubmit();
        } else if (e.key === "Escape") {
            setOpen(false);
            setIsCustomMode(false);
            setCustomValue("");
        }
    };

    const filteredOptions = options.filter(option =>
        option.label.toLowerCase().includes(searchValue.toLowerCase()) ||
        option.value.toLowerCase().includes(searchValue.toLowerCase())
    );

    const selectedOption = options.find(option => option.value === value);

    return (
        <Popover open={open} onOpenChange={setOpen}>
            <PopoverTrigger asChild>
                <Button
                    variant="outline"
                    role="combobox"
                    aria-expanded={open}
                    className={cn(
                        "w-full justify-between",
                        !value && "text-muted-foreground",
                        className
                    )}
                    disabled={disabled}
                    data-test-id={dataTestId}
                >
                    {value ? (
                        isCustomMode ? (
                            <span className="truncate">{value}</span>
                        ) : (
                            <span className="truncate">{selectedOption?.label || value}</span>
                        )
                    ) : (
                        placeholder
                    )}
                    <ChevronsUpDown className="ml-2 h-4 w-4 shrink-0 opacity-50" />
                </Button>
            </PopoverTrigger>
            <PopoverContent className="w-full p-0" align="start">
                <Command>
                    <CommandInput
                        placeholder={isCustomMode ? customPlaceholder : searchPlaceholder}
                        value={isCustomMode ? customValue : searchValue}
                        onValueChange={isCustomMode ? setCustomValue : setSearchValue}
                        onKeyDown={isCustomMode ? handleKeyDown : undefined}
                    />
                    <CommandList>
                        {!isCustomMode && (
                            <>
                                <CommandEmpty>{emptyText}</CommandEmpty>
                                <CommandGroup>
                                    {filteredOptions.map((option) => (
                                        <CommandItem
                                            key={option.value}
                                            value={option.value}
                                            onSelect={() => {
                                                onValueChange(option.value);
                                                setOpen(false);
                                                setSearchValue("");
                                            }}
                                        >
                                            <Check
                                                className={cn(
                                                    "mr-2 h-4 w-4",
                                                    value === option.value ? "opacity-100" : "opacity-0"
                                                )}
                                            />
                                            {option.label}
                                        </CommandItem>
                                    ))}
                                    {allowCustom && (
                                        <CommandItem
                                            onSelect={() => {
                                                setIsCustomMode(true);
                                                setCustomValue("");
                                            }}
                                            className="text-primary"
                                        >
                                            <Plus className="mr-2 h-4 w-4" />
                                            Add custom value
                                        </CommandItem>
                                    )}
                                </CommandGroup>
                            </>
                        )}
                        {isCustomMode && (
                            <div className="p-2">
                                <div className="flex gap-2">
                                    <Input
                                        value={customValue}
                                        onChange={(e) => setCustomValue(e.target.value)}
                                        onKeyDown={handleKeyDown}
                                        placeholder={customPlaceholder}
                                        className="flex-1"
                                    />
                                    <Button
                                        size="sm"
                                        onClick={handleCustomSubmit}
                                        disabled={!customValue.trim()}
                                    >
                                        Add
                                    </Button>
                                </div>
                                <Button
                                    variant="ghost"
                                    size="sm"
                                    className="mt-2 w-full"
                                    onClick={() => {
                                        setIsCustomMode(false);
                                        setCustomValue("");
                                    }}
                                >
                                    Cancel
                                </Button>
                            </div>
                        )}
                    </CommandList>
                </Command>
            </PopoverContent>
        </Popover>
    );
} 