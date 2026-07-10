#!/usr/bin/env python3
"""
OCR Scanner for Geiser Portal - Extracts text from handwritten forms
Usage: python ocr_scan.py <image_path> [--languages de,en]
Output: JSON with raw OCR text
"""

import sys
import json
import easyocr
from pathlib import Path

def scan_image(image_path, languages=['de', 'en']):
    """Scan image with EasyOCR and return extracted text"""
    try:
        # Initialize reader (caches model after first run)
        reader = easyocr.Reader(languages, gpu=False)
        
        # Read image
        result = reader.readtext(image_path)
        
        # Extract text and confidence
        extracted_text = []
        for detection in result:
            text = detection[1]
            confidence = detection[2]
            extracted_text.append({
                'text': text,
                'confidence': float(confidence)
            })
        
        return {
            'status': 'success',
            'text': extracted_text,
            'full_text': ' '.join([item['text'] for item in extracted_text])
        }
    
    except FileNotFoundError:
        return {
            'status': 'error',
            'message': f'Image file not found: {image_path}'
        }
    except Exception as e:
        return {
            'status': 'error',
            'message': str(e)
        }

if __name__ == '__main__':
    if len(sys.argv) < 2:
        print(json.dumps({
            'status': 'error',
            'message': 'Usage: ocr_scan.py <image_path>'
        }))
        sys.exit(1)
    
    image_path = sys.argv[1]
    languages = sys.argv[2].split(',') if len(sys.argv) > 2 else ['de', 'en']
    
    result = scan_image(image_path, languages)
    print(json.dumps(result, ensure_ascii=False, indent=2))
