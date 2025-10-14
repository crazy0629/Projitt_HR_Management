import { useState, useRef, useEffect } from "react";
import { Input } from '@/components/ui/input';

/**
 * SuggestionInput Component
 * 
 * A dropdown input component that allows the user to type and select suggestions.
 * Filters the suggestion list based on user input and allows selection to update the parent.
 * Useful for designation or tag selection.
 */

interface Suggestion {
  id: string;
  name: string;
}

export default function SuggestionInput({
  value,
  onChange,
  suggestions = [],
  placeholder = "Select Designation"
}: {
  value: string;
  onChange: (val: string) => void;
  suggestions: Suggestion[];
  placeholder?: string;
}) {
  /**
   * Component state for input value and dropdown visibility.
   */
  const [inputValue, setInputValue] = useState(value || "");
  const [showDropdown, setShowDropdown] = useState(false);
  const inputRef = useRef<HTMLInputElement>(null);

  /**
   * Sync internal state with external value prop
   */
  useEffect(() => {
    setInputValue(value || "");
  }, [value]);

  /**
   * Filters the list of suggestions based on input value (case insensitive),
   * excludes the exact match from showing again.
   */
  const filteredSuggestions = suggestions
    .filter(
      (s) =>
        s.name.toLowerCase().includes(inputValue.toLowerCase()) &&
        s.name !== inputValue
    );

  /**
   * Handles selection from dropdown:
   * Sets the selected name to the input and calls the parent `onChange` handler.
   */
  const handleSelect = (name: string) => {
    setInputValue(name);
    onChange(name);
    setShowDropdown(false);
    inputRef.current?.blur();
  };

  return (
    <div className="relative w-full">
      <Input
        ref={inputRef}
        id="suggestion-input"
        data-testid="suggestion-input"
        className="h-[48px] w-full border border-[#bcbcbc] rounded-[10px] px-3 outline-none text-gray-700"
        value={inputValue}
        placeholder={placeholder}
        autoComplete="off"
        onFocus={() => setShowDropdown(true)}
        onBlur={() => setTimeout(() => setShowDropdown(false), 100)}
        onChange={e => {
          setInputValue(e.target.value);
          onChange(e.target.value);
        }}
      />
      {showDropdown && filteredSuggestions.length > 0 && (
        <ul
          id="suggestion-dropdown"
          data-testid="suggestion-dropdown"
          className="absolute left-0 top-full mt-1 z-10 bg-white border border-gray-200 w-full rounded shadow max-h-48 overflow-y-auto"
        >
          {filteredSuggestions.map((s) => (
            <li
              key={s.id}
              id={`suggestion-item-${s.id}`}
              data-testid={`suggestion-item-${s.id}`}
              onMouseDown={() => handleSelect(s.name)}
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
