import { useState, useRef } from "react";

// Define the Suggestion type
interface Suggestion {
    id: string | number;
    name: string;
}

/**
 * TagInput component allows users to input and manage a dynamic list of tags.
 * 
 * @param tags - An array of current tags
 * @param setTags - A callback function to update the tags array
 * @param suggestions - (optional) An array of suggestion objects for dropdown
 * @param restrictToSuggestions - (optional) If true, only allows adding tags from suggestions
 * @returns JSX.Element
 */
export default function TagInput({
    tags,
    setTags,
    suggestions = [],
    restrictToSuggestions = false,
}: {
    tags: string[];
    setTags: (tags: string[]) => void;
    suggestions?: Suggestion[];
    restrictToSuggestions?: boolean;
}): JSX.Element {
    const [inputValue, setInputValue] = useState("");
    const [showDropdown, setShowDropdown] = useState(false);
    const [error, setError] = useState("");
    const inputRef = useRef<HTMLInputElement>(null);

    /**
     * Handles keyboard input events for the tag input field.
     * - Adds a new tag on 'Enter' if it doesn't already exist.
     * - Removes the last tag on 'Backspace' if input is empty.
     * - Shows error if restrictToSuggestions is true and input is not in suggestions.
     */
    const handleKeyDown = (e: React.KeyboardEvent<HTMLInputElement>): void => {
        if (e.key === "Enter" && inputValue.trim()) {
            e.preventDefault();

            if (restrictToSuggestions) {
                // Check if the input value exists in suggestions
                const suggestionExists = suggestions.some(
                    (s: Suggestion) => s.name.toLowerCase() === inputValue.trim().toLowerCase()
                );

                if (!suggestionExists) {
                    setError("Please select a tag from the suggestions");
                    return;
                }
            }

            if (!tags.includes(inputValue.trim())) {
                setTags([...tags, inputValue.trim()]);
                setInputValue("");
                setError("");
            }
        } else if (e.key === "Backspace" && inputValue === "") {
            setTags(tags.slice(0, -1));
        }
    };

    /**
     * Removes a tag at the specified index.
     * 
     * @param index - The index of the tag to remove
     */
    const removeTag = (index: number): void => {
        setTags(tags.filter((_, i) => i !== index));
    };

    // Filter suggestions to exclude already selected tags and match input
    const filteredSuggestions = (suggestions || []).filter(
        (s: Suggestion) => !tags.includes(s.name) && s.name.toLowerCase().includes(inputValue.toLowerCase())
    );

    // Handle suggestion click
    const handleAddTag = (tag: string) => {
        setTags([...tags, tag]);
        setInputValue("");
        setShowDropdown(false);
        setError("");
        inputRef.current?.focus();
    };

    // Clear error when input changes
    const handleInputChange = (e: React.ChangeEvent<HTMLInputElement>) => {
        setInputValue(e.target.value);
        if (error) setError("");
    };

    return (
        <div
            id="tag-input-wrapper"
            data-testid="tag-input-wrapper"
            className="p-[16px] border border-[#bcbcbc] rounded-[10px] w-full flex flex-wrap gap-[10px] relative"
        >
            {/* Render each tag with a remove button */}
            {tags.map((tag, index) => (
                <div
                    key={index}
                    id={`tag-${index}`}
                    data-testid={`tag-${index}`}
                    className="flex items-center bg-[#d6eeec] text-[#0d978b] px-3 py-1 rounded-full"
                >
                    <span id={`tag-label-${index}`} data-testid={`tag-label-${index}`}>
                        {tag}
                    </span>
                    <button
                        id={`remove-tag-${index}`}
                        data-testid={`remove-tag-${index}`}
                        onClick={() => removeTag(index)}
                        className="ml-2 focus:outline-none"
                        aria-label={`Remove ${tag}`}
                    >
                        ×
                    </button>
                </div>
            ))}

            {/* Input field for new tags */}
            <input
                id="tag-input-field"
                data-testid="tag-input-field"
                className={`flex-grow min-w-[100px] outline-none text-gray-700 ${error ? 'border-red-500' : ''}`}
                type="text"
                value={inputValue}
                ref={inputRef}
                autoComplete="off"
                onFocus={() => setShowDropdown(true)}
                onBlur={() => setTimeout(() => setShowDropdown(false), 100)}
                onChange={handleInputChange}
                onKeyDown={handleKeyDown}
                placeholder={restrictToSuggestions ? "Select from suggestions..." : "Add a skill..."}
                aria-label={restrictToSuggestions ? "Select from suggestions" : "Add a skill"}
            />

            {/* Error message */}
            {error && (
                <div className="text-red-500 text-sm mt-1 absolute top-full left-0">
                    {error}
                </div>
            )}

            {/* Suggestions dropdown */}
            {showDropdown && filteredSuggestions.length > 0 && (
                <ul className="absolute left-0 top-full mt-1 z-10 bg-white border border-gray-200 w-full rounded shadow max-h-48 overflow-y-auto">
                    {filteredSuggestions.map((s: Suggestion) => (
                        <li
                            key={s.id}
                            onMouseDown={() => handleAddTag(s.name)}
                            className="px-3 py-2 cursor-pointer hover:bg-gray-100"
                        >
                            {s.name}
                        </li>
                    ))}
                </ul>
            )}
        </div>
    );
}
