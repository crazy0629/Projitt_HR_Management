import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { EditableSelect } from "@/components/ui/editable-select";
import { ArrowLeft, GripVertical, Plus, Trash2 } from "lucide-react";
import { useState } from "react";

/**
 * HiringPipeline manages the creation and editing of a hiring process pipeline.
 * Users can import templates, start from scratch, add/edit/remove stages, and define stage types and tests.
 */
export default function HiringPipeline() {
    const [pipeline, setPipeLine] = useState<any[]>([])
    const [selectedStep, setSelectedStep] = useState<number | null>(null);
    const [state, setState] = useState('main')

    /**
     * Adds a new step to the pipeline after the given index.
     */
    const addStep = (index: number) => {
        const newPipeline = [...pipeline];
        newPipeline.splice(index + 1, 0, {
            title: 'New Step',
            type: 'New Type',
            test: ''
        });
        setPipeLine(newPipeline);
    };

    /**
     * Removes a step from the pipeline at the given index.
     */
    const removeStep = (index: number) => {
        const newPipeline = [...pipeline];
        newPipeline.splice(index, 1);
        setPipeLine(newPipeline);
    };

    /**
     * Initializes a new pipeline with one default step.
     */
    const startFromScratch = () => {
        setPipeLine([
            {
                title: 'New Step',
                type: 'New Type',
                test: ''
            },
        ])
    }

    return (
        <div className="w-full" data-test-id="hiring-pipeline-container">
            <div className="flex items-center justify-between gap-[8px] w-full">
                <p className="text-[20px]/[30px] font-semibold text-[#353535]" data-test-id="pipeline-title">Hiring Pipeline</p>
                <div className="flex items-center gap-[9px] cursor-pointer" data-test-id="template-checkbox">
                    <input type="checkbox" className="accent-[#0d978b] size-[13px]" />
                    <span className="text-[14px]/[16px] text-[#4b4b4b]">Set as Template</span>
                </div>
            </div>
            <div className='mt-[33px] border border-[#e9e9e9] rounded-[12px] bg-[#fafafa] min-h-[700px] h-auto flex xl:flex-row flex-col items-stretch w-full'>
                {state === 'main' && pipeline.length === 0 && (
                    <div className="flex w-full flex-col items-center justify-center gap-[16px]" data-test-id="empty-state-buttons">
                        <Button variant="outline" className="text-[#053834]" onClick={() => { setState('template') }} data-test-id="import-template-btn">Import Teamplate</Button>
                        <Button variant="outline" className="text-[#053834]" onClick={startFromScratch} data-test-id="start-from-scratch-btn">Start From Scratch</Button>
                    </div>
                )}
                {state === 'template' && pipeline.length === 0 && (
                    <div className="flex w-full flex-col items-center justify-center gap-[16px] px-[20px]" data-test-id="import-template-panel">
                        <div className="sm:w-auto w-full">
                            <div className="flex items-center gap-[12px] mb-[26px]">
                                <button className="w-[24px] h-[24px] rounded-full flex items-center justify-center cursor-pointer bg-white border border-[#e9e9e9]" onClick={() => setState('main')} data-test-id="back-to-main-btn">
                                    <ArrowLeft className="size-[16px]" />
                                </button>
                                <p className="text-[14px]/[20px] text-[#053834] font-medium">Import Template</p>
                            </div>
                            <Select>
                                <SelectTrigger className="h-[48px] sm:w-[360px] w-full" data-test-id="template-select-trigger">
                                    <SelectValue placeholder="Select a template" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="template1">Template 1</SelectItem>
                                    <SelectItem value="template2">Template 2</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                    </div>
                )}
                {pipeline.length > 0 && (
                    <>
                        <div className="flex-1 p-[22px] lg:order-2 order-1" data-test-id="pipeline-stages-list">
                            <button className="cursor-pointer text-[#787878] text-[14px]/[20px]" data-test-id="clear-pipeline-btn">Clear</button>
                            <div className="flex justify-center mt-[20px]">
                                <div>
                                    {pipeline.map((item, index) => (
                                        <div key={index} data-test-id={`pipeline-stage-${index}`}>
                                            <div className="flex items-center justify-center gap-[12px]">
                                                <GripVertical className="size-[20px] text-[#1c1c1c] cursor-grab active:cursor-grabbing" onDragStart={(e) => e.preventDefault()} />
                                                <div
                                                    className={`bg-white border rounded-[8px] p-[16px] flex justify-between items-center sm:w-[343px] w-[170px] cursor-pointer ${selectedStep === index ? 'border-[#0D978B]' : 'border-[#e9e9e9]'}`}
                                                    onClick={() => setSelectedStep(index)}
                                                    data-test-id={`pipeline-stage-card-${index}`}
                                                >
                                                    <div className="flex flex-col gap-[3px]">
                                                        <p className="text-[14px]/[22px] text-black">{item.title}</p>
                                                        <p className="text-[12px]/[18px] text-[#00d47d]">{item.type}</p>
                                                    </div>
                                                    <div className="w-[28px] h-[28px] rounded-full bg-[#d6eeec] text-[#0d978b] flex justify-center items-center text-[12px]">
                                                        {index < 10 ? "0" + (index + 1) : index + 1}
                                                    </div>
                                                </div>
                                                <button onClick={() => removeStep(index)} className="cursor-pointer text-[#1c1c1c] hover:text-[#0d978b]" data-test-id={`remove-stage-btn-${index}`}>
                                                    <Trash2 className="size-[14px]" />
                                                </button>
                                            </div>
                                            <div className="flex flex-col items-center justify-center mt-[-8px]">
                                                <div
                                                    className="w-[16px] h-[16px] rounded-full bg-[#4b4b4b] flex justify-center items-center cursor-pointer hover:bg-[#0d978b] hover:text-[#fff]"
                                                    onClick={() => addStep(index)}
                                                    data-test-id={`add-stage-btn-after-${index}`}
                                                >
                                                    <Plus className="size-[12px] text-[#fff]" />
                                                </div>
                                                {index < pipeline.length - 1 && <div className="w-[1px] h-[56px] bg-[#8f8f8f]" />}
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            </div>
                        </div>
                        {selectedStep !== null && (
                            <div className="xl:w-[273px] w-full lg:order-2 order-1 bg-white border-l border-[#e9e9e9] py-[27px] px-[20px]" data-test-id="stage-details-panel">
                                <p className="text-[14px]/[22px] text-black">Stage Details</p>
                                <div className="mt-[26px]">
                                    <div>
                                        <p className="text-[12px]/[16px] text-[#1c1c1c]">Stage Name</p>
                                        <EditableSelect
                                            value={pipeline[selectedStep].title}
                                            onValueChange={(value) => {
                                                const newPipeline = [...pipeline];
                                                newPipeline[selectedStep].title = value;
                                                setPipeLine(newPipeline);
                                            }}
                                            options={[
                                                { value: "Application Review", label: "Application Review" },
                                                { value: "Phone Screening", label: "Phone Screening" },
                                                { value: "Technical Interview", label: "Technical Interview" },
                                                { value: "Final Interview", label: "Final Interview" },
                                                { value: "Reference Check", label: "Reference Check" },
                                                { value: "Offer", label: "Offer" },
                                                { value: "Hired", label: "Hired" },
                                            ]}
                                            placeholder="Enter stage name"
                                            searchPlaceholder="Search stage names..."
                                            allowCustom={true}
                                            customPlaceholder="Enter custom stage name..."
                                            dataTestId="stage-name-editable-select"
                                        />

                                        <p className="text-[12px]/[16px] text-[#1c1c1c] mt-[18px]">Type</p>
                                        <Select
                                            value={pipeline[selectedStep].type}
                                            onValueChange={(value) => {
                                                const newPipeline = [...pipeline];
                                                newPipeline[selectedStep].type = value;
                                                setPipeLine(newPipeline);
                                            }}
                                        >
                                            <SelectTrigger data-test-id="stage-type-select-trigger">
                                                <SelectValue placeholder="Select a type" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem value="live-interview">Live Interview</SelectItem>
                                                <SelectItem value="psychometric-test">Psychometric Test</SelectItem>
                                                <SelectItem value="coding-interview">Coding Interview</SelectItem>
                                            </SelectContent>
                                        </Select>
                                        <p className="text-[12px]/[16px] text-[#1c1c1c] mt-[18px]">Select Test</p>
                                        <Select
                                            value={pipeline[selectedStep].test}
                                            onValueChange={(value) => {
                                                const newPipeline = [...pipeline];
                                                newPipeline[selectedStep].test = value;
                                                setPipeLine(newPipeline);
                                            }}
                                        >
                                            <SelectTrigger data-test-id="stage-test-select-trigger">
                                                <SelectValue placeholder="Select a test" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem value="cognitive-assesment">Cognitive Assesment</SelectItem>
                                                <SelectItem value="technical-assesment">Technical Assesment</SelectItem>
                                            </SelectContent>
                                        </Select>
                                    </div>
                                </div>
                            </div>
                        )}
                    </>
                )}
            </div>
        </div>
    );
}
