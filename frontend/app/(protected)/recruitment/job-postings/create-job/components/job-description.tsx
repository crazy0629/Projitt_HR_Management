/**
 * JobDescription.tsx
 *
 * This component provides a job description editor with rich text capabilities,
 * AI-based content generation with selectable tone styles, and file attachments.
 * It uses react-quill for text editing and supports validation, undo/redo, and file management.
 */

import 'react-quill-new/dist/quill.snow.css';
import ReactQuill from 'react-quill-new';
import { useState, useRef, JSX } from 'react';
import { CheckLine, Cloud, File, HardDrive, Link, Redo, Undo } from 'lucide-react';
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from '@/components/ui/popover';
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { uploadMedia } from '@/api/media';

export default function JobDescription({
    jobData,
    setJobData,
    errors = {},
    triggerValidation = false,
}: any): JSX.Element {
    const [files, setFiles] = useState<File[]>([]);
    const [selectedStyle, setSelectedStyle] = useState<string>('Formal');
    const quillRef = useRef<ReactQuill | null>(null);

    /**
     * Handles file input changes
     */
    const handleFileChange = async (e: React.ChangeEvent<HTMLInputElement>): Promise<void> => {
        const files = e.target.files;
        if (!files) return;
        const formData: any = new FormData();
        formData.append('media', files[0]);
        const response = await uploadMedia(formData);
        console.log(response);
        if (files) {
            setFiles([...files, ...Array.from(files)]);
        }
    };

    /**
     * Removes file at given index
     */
    const removeFile = (index: number): void => {
        const updated = [...files];
        updated.splice(index, 1);
        setFiles(updated);
    };

    /**
     * Performs undo operation on Quill editor
     */
    const handleUndo = (): void => {
        const editor = quillRef.current?.getEditor();
        editor?.history.undo();
    };

    /**
     * Performs redo operation on Quill editor
     */
    const handleRedo = (): void => {
        const editor = quillRef.current?.getEditor();
        editor?.history.redo();
    };

    const modules = {
        toolbar: {
            container: '#custom-toolbar',
        },
        history: {
            delay: 1000,
            maxStack: 100,
            userOnly: true,
        },
    };

    return (
        <div
            id="job-description-component"
            data-testid="job-description-component"
        >
            <h1
                className="text-[20px]/[30px] font-semibold text-[#353535]"
                id="job-description-title"
                data-testid="job-description-title"
            >
                Job Description
            </h1>

            <div className="flex justify-between mt-[34px]">
                <p
                    className="text-[#1c1c1c] text-[14px]/[16px]"
                    id="job-description-label"
                    data-testid="job-description-label"
                >
                    Job Description *
                </p>

                <div
                    className="flex gap-[8px] items-center cursor-pointer"
                    id="write-with-ai-group"
                    data-testid="write-with-ai-group"
                >
                    <img
                        src="/images/icons/ai-line.png"
                        alt=""
                        className="w-[20px] h-[20px]"
                        id="ai-icon"
                        data-testid="ai-icon"
                    />
                    <Dialog>
                        <DialogTrigger asChild>
                            <p
                                className="text-[14px]/[16px] text-[#0d978b]"
                                id="write-with-ai-trigger"
                                data-testid="write-with-ai-trigger"
                            >
                                Write with AI
                            </p>
                        </DialogTrigger>
                        <DialogTitle />
                        <DialogContent
                            className="w-[90%] md:w-[406px]  pl-0 pr-0 pt-0 pb-0 !rounded-[16px]"
                            id="ai-dialog"
                            data-testid="ai-dialog"
                        >
                            <div className="flex items-center gap-[8px] border-b border-[#e9e9e9] pb-[16px] pl-[18px] pt-[20px]">
                                <img
                                    src="/images/icons/ai-line.png"
                                    alt=""
                                    className="w-[18px] h-[18px]"
                                />
                                <p className="text-[14px]/[16px] text-[#353535]">Generate</p>
                            </div>
                            <div className="px-[15px] py-[12px]">
                                <div className="flex gap-[8px] items-center flex-wrap">
                                    {['✍️ Formal', '😎 Friendly', '💪 Inspirational'].map(style => (
                                        <span
                                            key={style}
                                            className={`px-[12px] py-[4px] rounded-[21px] cursor-pointer text-[12px]/[16px] text-[#626262] ${selectedStyle === style
                                                ? 'bg-[#0d978b] text-[#fff]'
                                                : 'bg-[#e9e9e9]'
                                                }`}
                                            onClick={() => setSelectedStyle(style)}
                                            id={`ai-tone-${style.toLowerCase()}`}
                                            data-testid={`ai-tone-${style.toLowerCase()}`}
                                        >
                                            {style}
                                        </span>
                                    ))}
                                </div>
                                <textarea
                                    className="w-full h-[140px] mt-[12px] focus:outline-none focus:ring-0 focus:border-none"
                                    placeholder="Enter any additional context"
                                    id="ai-context-textarea"
                                    data-testid="ai-context-textarea"
                                />
                                <Dialog>
                                    <DialogTrigger asChild>
                                        <Button
                                            className="mt-[12px] w-full h-[42px] font-semibold text-[14px]/[20px]"
                                            id="ai-generate-button"
                                            data-testid="ai-generate-button"
                                        >
                                            Generate
                                        </Button>
                                    </DialogTrigger>
                                    <DialogTitle />
                                    <DialogContent close={false} className="w-[90%] md:w-[406px] px-0 pt-[16px] pb-0 !rounded-[16px] ">
                                        <div className='h-[300px] overflow-y-auto  px-[18px]'>
                                            <p className='text-[12px]/[24px] font-bold text-[#353535]'>Senior Data Analyst Position Overview</p>
                                            <p className='text-[12px]/[24px] text-[#353535]'>We are seeking an experienced Senior Data Analyst to join our growing analytics team. The ideal candidate will transform complex data into actionable insights that drive business decisions.</p>
                                        </div>
                                        <div className='py-[16.5px] px-[49.5px] grid grid-cols-2'>
                                            <div className='text-[#0d978b] flex items-center gap-[8px] justify-center text-[14px]/[16px] border-r border-[#e9e9e9] cursor-pointer'><CheckLine className='size-[20px]' /> Accept & Insert</div>
                                            <div className='text-[#4b4b4b] flex items-center gap-[8px] justify-center text-[14px]/[16px] cursor-pointer'><Redo className='size-[20px]' /> Try again</div>
                                        </div>
                                    </DialogContent>
                                </Dialog>
                            </div>
                        </DialogContent>
                    </Dialog>
                </div>
            </div>

            <div className="mt-[12px]">
                <div
                    id="custom-toolbar"
                    data-testid="custom-toolbar"
                    className="w-full flex justify-between flex-wrap"
                >
                    <div className="flex sm:gap-[14px] items-center">
                        <button className="ql-bold" />
                        <button className="ql-italic" />
                        <button className="ql-underline" />
                        <button className="ql-align" value="" />
                        <button className="ql-align" value="center" />
                        <button className="ql-align" value="right" />
                        <button className="ql-align" value="justify" />
                        <button className="ql-link" />
                    </div>
                    <div className="flex sm:gap-[14px] items-center">
                        <button type="button" onClick={handleUndo} data-testid="undo-button">
                            <Undo className="text-[#4b4b4b]" />
                        </button>
                        <button type="button" onClick={handleRedo} data-testid="redo-button">
                            <Redo className="text-[#4b4b4b]" />
                        </button>
                    </div>
                </div>

                <ReactQuill
                    ref={quillRef}
                    value={jobData.description || ''}
                    onChange={(value) => setJobData({ ...jobData, description: value })}
                    placeholder="Enter the job description..."
                    theme="snow"
                    modules={modules}
                    className="w-full h-[400px] rounded-[12px]"
                    id="job-description-editor"
                    data-testid="job-description-editor"
                />

                {triggerValidation && errors.description && (
                    <span
                        className="text-red-500 text-xs"
                        id="job-description-error"
                        data-testid="job-description-error"
                    >
                        {errors.description}
                    </span>
                )}

                <input
                    id="fileInput"
                    type="file"
                    multiple
                    onChange={handleFileChange}
                    className="hidden"
                    data-testid="job-description-file-input"
                />

                <div
                    className="flex flex-wrap gap-2 ml-2 mt-[-55px] relative z-[10]"
                    id="file-list"
                    data-testid="job-description-file-list"
                >
                    {files.map((file, index) => (
                        <div
                            key={index}
                            className="flex items-center min-w-[210px] justify-between border-[#e9e9e9] bg-[#FAFAFA] px-[16px] py-[12px] border rounded-[6.52px]"
                            id={`job-description-file-${index}`}
                            data-testid={`job-description-file-${index}`}
                        >
                            <div className="flex items-center">
                                <File className="size-[16px] text-[#0d978b]" />
                                <span className="text-sm truncate max-w-[200px] ml-[5px]">
                                    {file.name}
                                </span>
                            </div>
                            <button
                                onClick={() => removeFile(index)}
                                className="ml-2 text-[#4b4b4b] cursor-pointer"
                                id={`remove-file-${index}`}
                                data-testid={`remove-file-${index}`}
                            >
                                ×
                            </button>
                        </div>
                    ))}
                </div>

                <div
                    className={`flex justify-between items-center ${files.length > 0 ? 'mt-[20px]' : 'mt-[70px]'
                        }`}
                >
                    <Dialog>
                        <DialogTrigger asChild>
                            <button
                                type="button"
                                className="flex items-center gap-[4px] text-[#4b4b4b]"
                                id="attach-files-button"
                                data-testid="attach-files-button"
                            >
                                <Link className="size-[16px]" />
                                <span className="text-[14px]/[20px]">Attach Files</span>
                            </button>
                        </DialogTrigger>
                        <DialogTitle />
                        <DialogContent>
                            <DialogHeader>
                                <DialogTitle>Attach Files</DialogTitle>
                            </DialogHeader>
                            <div className='flex gap-[10px] w-full mt-[10px]'>
                                <button className='flex flex-col w-full items-center gap-[10px] border-[#717171] border-dashed border rounded-[6.52px] py-[10px] hover:border-[#0d978b] hover:bg-[#dcfffc] cursor-pointer' onClick={() => { document.getElementById('fileInput')?.click() }}>
                                    <HardDrive className="size-[25px] text-[#0d978b]" />
                                    <span className="text-[14px]/[20px] text-[#4b4b4b]">From Local</span>
                                </button>
                                <button className='flex flex-col w-full items-center gap-[10px] border-[#717171] border-dashed border rounded-[6.52px] py-[10px] hover:border-[#0d978b] hover:bg-[#dcfffc] cursor-pointer' onClick={() => { }}>
                                    <Cloud className="size-[25px] text-[#0d978b]" />
                                    <span className="text-[14px]/[20px] text-[#4b4b4b]">From Server</span>
                                </button>
                            </div>
                        </DialogContent>
                    </Dialog>

                    <div className="gap-[4px] flex items-center">
                        <input
                            type="checkbox"
                            className="accent-[#0d978b] size-[13px]"
                            id="default-template-checkbox"
                            data-testid="default-template-checkbox"
                        />
                        <p className="text-[14px]/[20px] text-[#4b4b4b]">
                            Set as default template
                        </p>
                    </div>
                </div>
            </div>
        </div >
    );
}
