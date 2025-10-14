import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { Popover, PopoverContent, PopoverTrigger } from "@/components/ui/popover";
import { Calendar } from "@/components/ui/calendar";
import { CalendarDays } from "lucide-react";
import moment from "moment";
import { Button } from "@/components/ui/button";
import { useState } from "react";
import { cn } from "@/lib/utils";

export default function Education() {
    const [education, setEducation] = useState({
        education: {
            school: '',
            degree: '',
            fieldOfStudy: '',
        },
        certificates: {
            certificate: '',
            certificateNumber: '',
            issueDate: new Date(),
            expirationDate: new Date(),
        },
    });
    return (
        <div>
            <Label>Education</Label>
            <Input placeholder="School or University" className="w-full h-[48px]" data-test-id="education-school" />
            <Select>
                <SelectTrigger className="w-full h-[48px] mt-[14px]" data-test-id="education-degree">
                    <SelectValue placeholder="Select a Degree" />
                </SelectTrigger>
                <SelectContent>
                    <SelectItem value="bachelor">Bachelor's</SelectItem>
                    <SelectItem value="masters">Master's</SelectItem>
                    <SelectItem value="doctorate">Doctorate</SelectItem>
                    <SelectItem value="associate">Associate</SelectItem>
                </SelectContent>
            </Select>
            <Input placeholder="Field of Study" className="w-full h-[48px] mt-[14px]" data-test-id="education-field" />
            <div className="mt-[24px] border-t border-[#e9e9e9] pt-[24px]">
                <Label>Certifications</Label>
                <Input placeholder="Certification" className="w-full h-[48px]" data-test-id="certificate-name" />
                <Input placeholder="Certification Number" className="w-full h-[48px] mt-[14px]" data-test-id="certificate-number" />
                <div className="grid xl:grid-cols-2 grid-cols-1 gap-[16px] mt-[14px]">
                    <div>
                        <Label>Issued Date</Label>
                        <Popover>
                            <PopoverTrigger asChild>
                                <Button
                                    mode="input"
                                    variant="outline"
                                    id="date"
                                    className={cn(
                                        'w-full h-[48px] data-[state=open]:border-primary',
                                        !education.certificates.issueDate && 'text-muted-foreground',
                                    )}
                                    data-test-id="certificate-issue-date"
                                >
                                    <CalendarDays className="-ms-0.5" />
                                    {moment(education.certificates.issueDate).format('DD/MM/YYYY')}
                                </Button>
                            </PopoverTrigger>
                            <PopoverContent className="w-auto p-0" align="start">
                                <Calendar
                                    initialFocus
                                    mode="single"
                                    defaultMonth={education.certificates.issueDate || new Date()}
                                    selected={education.certificates.issueDate}
                                    onSelect={(e) => setEducation({
                                        ...education,
                                        certificates: {
                                            ...education.certificates,
                                            issueDate: e as Date
                                        }
                                    })}
                                    numberOfMonths={1}
                                />
                            </PopoverContent>
                        </Popover>
                    </div>
                    <div>
                        <Label>Expiration Date</Label>
                        <Popover>
                            <PopoverTrigger asChild>
                                <Button
                                    mode="input"
                                    variant="outline"
                                    id="date"
                                    className={cn(
                                        'w-full h-[48px] data-[state=open]:border-primary',
                                        !education.certificates.expirationDate && 'text-muted-foreground',
                                    )}
                                    data-test-id="certificate-expiration-date"
                                >
                                    <CalendarDays className="-ms-0.5" />
                                    {moment(education.certificates.expirationDate).format('DD/MM/YYYY')}
                                </Button>
                            </PopoverTrigger>
                            <PopoverContent className="w-auto p-0" align="start">
                                <Calendar
                                    initialFocus
                                    mode="single"
                                    defaultMonth={education.certificates.expirationDate || new Date()}
                                    selected={education.certificates.expirationDate}
                                    onSelect={(e) => setEducation({
                                        ...education,
                                        certificates: {
                                            ...education.certificates,
                                            expirationDate: e as Date
                                        }
                                    })}
                                    numberOfMonths={1}
                                />
                            </PopoverContent>
                        </Popover>
                    </div>
                </div>
            </div>
        </div>
    )
}