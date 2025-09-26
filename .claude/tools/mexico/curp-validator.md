# CURP 검증 도구

멕시코 개인식별번호(CURP) 검증 및 생성 시스템을 구현하는 도구입니다.

## 사용법
```
/tools/mexico:curp-validator [작업] [옵션]
```

## 기본 동작

당신은 멕시코 CURP(Clave Única de Registro de Población) 전문가입니다. CURP 검증, 생성, 파싱 기능을 구현하여 사용자 등록 시스템에 통합해야 합니다.

### CURP 형식 구조

CURP는 18자리 영숫자 조합으로 구성됩니다:
- **위치 1-2**: 성의 첫 번째 자음과 모음
- **위치 3-4**: 이름의 첫 번째 자음과 모음
- **위치 5-10**: 생년월일 (YYMMDD)
- **위치 11**: 성별 (H=남성, M=여성)
- **위치 12-13**: 출생 주 코드
- **위치 14-16**: 성과 이름의 내부 자음
- **위치 17**: 동명이인 구분 (0-9, A-Z)
- **위치 18**: 검증 숫자 (0-9)

### 구현할 기능

#### 1. CURP 검증 함수 (PHP)
```php
<?php

namespace App\Services\Mexico;

use App\Exceptions\InvalidCURPException;

class CURPValidator
{
    private const STATES = [
        'AS' => 'Aguascalientes',
        'BC' => 'Baja California',
        'BS' => 'Baja California Sur',
        'CC' => 'Campeche',
        'CL' => 'Coahuila',
        'CM' => 'Colima',
        'CS' => 'Chiapas',
        'CH' => 'Chihuahua',
        'DF' => 'Ciudad de México',
        'DG' => 'Durango',
        'GT' => 'Guanajuato',
        'GR' => 'Guerrero',
        'HG' => 'Hidalgo',
        'JC' => 'Jalisco',
        'MC' => 'México',
        'MN' => 'Michoacán',
        'MS' => 'Morelos',
        'NT' => 'Nayarit',
        'NL' => 'Nuevo León',
        'OC' => 'Oaxaca',
        'PL' => 'Puebla',
        'QT' => 'Querétaro',
        'QR' => 'Quintana Roo',
        'SP' => 'San Luis Potosí',
        'SL' => 'Sinaloa',
        'SR' => 'Sonora',
        'TC' => 'Tabasco',
        'TS' => 'Tamaulipas',
        'TL' => 'Tlaxcala',
        'VZ' => 'Veracruz',
        'YN' => 'Yucatán',
        'ZS' => 'Zacatecas',
        'NE' => 'Extranjero', // Born abroad
    ];

    private const FORBIDDEN_WORDS = [
        'BUEI', 'BUEY', 'CACA', 'CACO', 'CAGA', 'CAGO', 'CAKA', 'CAKO',
        'COGE', 'COGI', 'COJA', 'COJE', 'COJI', 'COJO', 'COLA', 'CULO',
        'FALO', 'FETO', 'GETA', 'GUEI', 'GUEY', 'JETA', 'JOTO', 'KACA',
        'KACO', 'KAGA', 'KAGO', 'KAKA', 'KAKO', 'KOGE', 'KOGI', 'KOJA',
        'KOJE', 'KOJI', 'KOJO', 'KOLA', 'KULO', 'LILO', 'LOCA', 'LOCO',
        'LOKA', 'LOKO', 'MAME', 'MAMO', 'MEAR', 'MEAS', 'MEON', 'MIAR',
        'MION', 'MOCO', 'MOKO', 'MULA', 'MULO', 'NACA', 'NACO', 'PEDA',
        'PEDO', 'PENE', 'PIPI', 'PITO', 'POPO', 'PUTA', 'PUTO', 'QULO',
        'RATA', 'ROBA', 'ROBE', 'ROBI', 'ROBO', 'RUIN', 'SENO', 'TETA',
        'VACA', 'VAGA', 'VAGO', 'VAKA', 'VUEI', 'VUEY', 'WUEI', 'WUEY'
    ];

    public function validate(string $curp): bool
    {
        try {
            $this->validateFormat($curp);
            $this->validateDate($curp);
            $this->validateState($curp);
            $this->validateCheckDigit($curp);
            return true;
        } catch (InvalidCURPException $e) {
            return false;
        }
    }

    public function validateWithDetails(string $curp): array
    {
        $errors = [];

        try {
            $this->validateFormat($curp);
        } catch (InvalidCURPException $e) {
            $errors[] = $e->getMessage();
        }

        try {
            $this->validateDate($curp);
        } catch (InvalidCURPException $e) {
            $errors[] = $e->getMessage();
        }

        try {
            $this->validateState($curp);
        } catch (InvalidCURPException $e) {
            $errors[] = $e->getMessage();
        }

        try {
            $this->validateCheckDigit($curp);
        } catch (InvalidCURPException $e) {
            $errors[] = $e->getMessage();
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'data' => empty($errors) ? $this->parseCURP($curp) : null,
        ];
    }

    public function parseCURP(string $curp): array
    {
        if (!$this->validate($curp)) {
            throw new InvalidCURPException('Invalid CURP format');
        }

        $birthYear = 2000 + (int)substr($curp, 4, 2);
        if ($birthYear > date('Y')) {
            $birthYear -= 100;
        }

        return [
            'curp' => strtoupper($curp),
            'birth_date' => sprintf(
                '%04d-%02d-%02d',
                $birthYear,
                (int)substr($curp, 6, 2),
                (int)substr($curp, 8, 2)
            ),
            'gender' => substr($curp, 10, 1) === 'H' ? 'male' : 'female',
            'state_code' => substr($curp, 11, 2),
            'state_name' => self::STATES[substr($curp, 11, 2)] ?? 'Unknown',
            'discriminator' => substr($curp, 16, 1),
            'check_digit' => substr($curp, 17, 1),
        ];
    }

    private function validateFormat(string $curp): void
    {
        $curp = strtoupper(trim($curp));

        if (strlen($curp) !== 18) {
            throw new InvalidCURPException('CURP must be exactly 18 characters');
        }

        if (!preg_match('/^[A-Z]{4}[0-9]{6}[HM][A-Z]{2}[A-Z0-9]{3}[0-9]$/', $curp)) {
            throw new InvalidCURPException('Invalid CURP format');
        }

        $firstFour = substr($curp, 0, 4);
        if (in_array($firstFour, self::FORBIDDEN_WORDS)) {
            throw new InvalidCURPException('CURP contains forbidden word');
        }
    }

    private function validateDate(string $curp): void
    {
        $year = (int)substr($curp, 4, 2);
        $month = (int)substr($curp, 6, 2);
        $day = (int)substr($curp, 8, 2);

        $fullYear = $year + (($year <= date('y')) ? 2000 : 1900);

        if (!checkdate($month, $day, $fullYear)) {
            throw new InvalidCURPException('Invalid birth date in CURP');
        }
    }

    private function validateState(string $curp): void
    {
        $stateCode = substr($curp, 11, 2);

        if (!array_key_exists($stateCode, self::STATES)) {
            throw new InvalidCURPException('Invalid state code in CURP');
        }
    }

    private function validateCheckDigit(string $curp): void
    {
        $expectedDigit = $this->calculateCheckDigit(substr($curp, 0, 17));
        $actualDigit = substr($curp, 17, 1);

        if ($expectedDigit !== $actualDigit) {
            throw new InvalidCURPException('Invalid check digit in CURP');
        }
    }

    private function calculateCheckDigit(string $curp17): string
    {
        $values = [
            '0' => 0, '1' => 1, '2' => 2, '3' => 3, '4' => 4, '5' => 5,
            '6' => 6, '7' => 7, '8' => 8, '9' => 9, 'A' => 10, 'B' => 11,
            'C' => 12, 'D' => 13, 'E' => 14, 'F' => 15, 'G' => 16, 'H' => 17,
            'I' => 18, 'J' => 19, 'K' => 20, 'L' => 21, 'M' => 22, 'N' => 23,
            'Ñ' => 24, 'O' => 25, 'P' => 26, 'Q' => 27, 'R' => 28, 'S' => 29,
            'T' => 30, 'U' => 31, 'V' => 32, 'W' => 33, 'X' => 34, 'Y' => 35,
            'Z' => 36,
        ];

        $sum = 0;
        for ($i = 0; $i < 17; $i++) {
            $char = substr($curp17, $i, 1);
            $sum += $values[$char] * (18 - $i);
        }

        $remainder = $sum % 10;
        return (string)(10 - $remainder);
    }
}
```

#### 2. Livewire 입력 컴포넌트
```php
<?php

namespace App\Livewire\Mexico;

use App\Services\Mexico\CURPValidator;
use Livewire\Component;

class CurpInput extends Component
{
    public string $curp = '';
    public array $validation = ['valid' => null, 'errors' => []];
    public array $parsedData = [];
    public bool $realtime = true;

    protected $rules = [
        'curp' => 'required|string|size:18|curp_valid',
    ];

    public function mount($value = '', $realtime = true)
    {
        $this->curp = strtoupper($value);
        $this->realtime = $realtime;

        if (!empty($this->curp)) {
            $this->validateCurp();
        }
    }

    public function updatedCurp()
    {
        $this->curp = strtoupper($this->curp);

        if ($this->realtime && strlen($this->curp) >= 18) {
            $this->validateCurp();
        }
    }

    public function validateCurp()
    {
        if (empty($this->curp)) {
            $this->resetValidation();
            return;
        }

        $validator = new CURPValidator();
        $result = $validator->validateWithDetails($this->curp);

        $this->validation = [
            'valid' => $result['valid'],
            'errors' => $result['errors'],
        ];

        if ($result['valid']) {
            $this->parsedData = $result['data'];
            $this->dispatch('curp-validated', $this->parsedData);
        } else {
            $this->parsedData = [];
            $this->dispatch('curp-invalid', $result['errors']);
        }
    }

    private function resetValidation()
    {
        $this->validation = ['valid' => null, 'errors' => []];
        $this->parsedData = [];
    }

    public function render()
    {
        return view('livewire.mexico.curp-input');
    }
}
```

#### 3. Blade 템플릿
```blade
{{-- resources/views/livewire/mexico/curp-input.blade.php --}}
<div class="form-control w-full">
    <label class="label">
        <span class="label-text">{{ __('forms.curp') }}</span>
        <span class="label-text-alt text-info">{{ __('forms.curp_help') }}</span>
    </label>

    <div class="input-group">
        <input
            type="text"
            wire:model.live="curp"
            maxlength="18"
            placeholder="AAAA999999HDFRRR99"
            class="input input-bordered w-full font-mono uppercase
                   {{ $validation['valid'] === true ? 'input-success' : '' }}
                   {{ $validation['valid'] === false ? 'input-error' : '' }}"
            style="text-transform: uppercase;"
        />

        @if($validation['valid'] === true)
            <span class="input-group-text bg-success text-success-content">
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                </svg>
            </span>
        @elseif($validation['valid'] === false)
            <span class="input-group-text bg-error text-error-content">
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                </svg>
            </span>
        @endif
    </div>

    {{-- 에러 메시지 --}}
    @if(!empty($validation['errors']))
        <div class="label">
            @foreach($validation['errors'] as $error)
                <span class="label-text-alt text-error">{{ __("validation.curp.{$error}") }}</span>
            @endforeach
        </div>
    @endif

    {{-- 파싱된 정보 표시 --}}
    @if(!empty($parsedData))
        <div class="mt-2 p-3 bg-base-200 rounded-lg text-sm">
            <div class="grid grid-cols-2 gap-2">
                <span><strong>{{ __('curp.birth_date') }}:</strong> {{ $parsedData['birth_date'] }}</span>
                <span><strong>{{ __('curp.gender') }}:</strong> {{ __("curp.gender.{$parsedData['gender']}") }}</span>
                <span><strong>{{ __('curp.state') }}:</strong> {{ $parsedData['state_name'] }}</span>
                <span><strong>{{ __('curp.code') }}:</strong> {{ $parsedData['state_code'] }}</span>
            </div>
        </div>
    @endif
</div>
```

#### 4. React 컴포넌트
```typescript
// components/forms/CURPInput.tsx
import React, { useState, useEffect } from 'react';
import { useTranslation } from 'react-i18next';

interface CURPData {
  curp: string;
  birthDate: string;
  gender: 'male' | 'female';
  stateCode: string;
  stateName: string;
  valid: boolean;
  errors: string[];
}

interface CURPInputProps {
  value?: string;
  onChange: (value: string, data?: CURPData) => void;
  className?: string;
  disabled?: boolean;
  required?: boolean;
}

export const CURPInput: React.FC<CURPInputProps> = ({
  value = '',
  onChange,
  className = '',
  disabled = false,
  required = false,
}) => {
  const { t } = useTranslation();
  const [curp, setCurp] = useState(value.toUpperCase());
  const [validation, setValidation] = useState<CURPData | null>(null);
  const [isValidating, setIsValidating] = useState(false);

  useEffect(() => {
    if (curp.length === 18) {
      validateCURP(curp);
    } else {
      setValidation(null);
    }
  }, [curp]);

  const validateCURP = async (curpValue: string) => {
    setIsValidating(true);

    try {
      const response = await fetch('/api/validate-curp', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
        },
        body: JSON.stringify({ curp: curpValue }),
      });

      const result = await response.json();
      setValidation(result);

      if (result.valid) {
        onChange(curpValue, result);
      } else {
        onChange(curpValue);
      }
    } catch (error) {
      console.error('CURP validation error:', error);
      setValidation({
        curp: curpValue,
        valid: false,
        errors: ['validation_failed'],
        birthDate: '',
        gender: 'male',
        stateCode: '',
        stateName: '',
      });
    } finally {
      setIsValidating(false);
    }
  };

  const handleChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    const newValue = e.target.value.toUpperCase().slice(0, 18);
    setCurp(newValue);
    onChange(newValue);
  };

  const getInputClass = () => {
    let baseClass = 'input input-bordered w-full font-mono uppercase';

    if (validation?.valid === true) {
      baseClass += ' input-success';
    } else if (validation?.valid === false) {
      baseClass += ' input-error';
    }

    return `${baseClass} ${className}`;
  };

  return (
    <div className="form-control w-full">
      <label className="label">
        <span className="label-text">
          {t('forms.curp')}
          {required && <span className="text-error ml-1">*</span>}
        </span>
        <span className="label-text-alt text-info">
          {t('forms.curp_help')}
        </span>
      </label>

      <div className="input-group">
        <input
          type="text"
          value={curp}
          onChange={handleChange}
          maxLength={18}
          placeholder="AAAA999999HDFRRR99"
          className={getInputClass()}
          disabled={disabled}
          required={required}
        />

        {isValidating && (
          <span className="input-group-text">
            <span className="loading loading-spinner loading-sm" />
          </span>
        )}

        {!isValidating && validation?.valid === true && (
          <span className="input-group-text bg-success text-success-content">
            <svg className="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
              <path fillRule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clipRule="evenodd" />
            </svg>
          </span>
        )}

        {!isValidating && validation?.valid === false && (
          <span className="input-group-text bg-error text-error-content">
            <svg className="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
              <path fillRule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clipRule="evenodd" />
            </svg>
          </span>
        )}
      </div>

      {validation?.errors && validation.errors.length > 0 && (
        <div className="label">
          {validation.errors.map((error, index) => (
            <span key={index} className="label-text-alt text-error">
              {t(`validation.curp.${error}`)}
            </span>
          ))}
        </div>
      )}

      {validation?.valid && (
        <div className="mt-2 p-3 bg-base-200 rounded-lg text-sm">
          <div className="grid grid-cols-2 gap-2">
            <span>
              <strong>{t('curp.birth_date')}:</strong> {validation.birthDate}
            </span>
            <span>
              <strong>{t('curp.gender')}:</strong> {t(`curp.gender.${validation.gender}`)}
            </span>
            <span>
              <strong>{t('curp.state')}:</strong> {validation.stateName}
            </span>
            <span>
              <strong>{t('curp.code')}:</strong> {validation.stateCode}
            </span>
          </div>
        </div>
      )}
    </div>
  );
};
```

#### 5. API 라우트 및 검증 규칙
```php
// routes/api.php
Route::post('/validate-curp', [MexicoValidationController::class, 'validateCURP']);

// app/Http/Controllers/MexicoValidationController.php
public function validateCURP(Request $request)
{
    $request->validate([
        'curp' => 'required|string|size:18',
    ]);

    $validator = new CURPValidator();
    $result = $validator->validateWithDetails($request->curp);

    return response()->json($result);
}

// app/Rules/CurpValid.php
class CurpValid implements Rule
{
    public function passes($attribute, $value)
    {
        $validator = new CURPValidator();
        return $validator->validate($value);
    }

    public function message()
    {
        return 'The :attribute must be a valid CURP.';
    }
}
```

### 출력 형식

실행 후 다음 컴포넌트들이 생성됩니다:

```markdown
## 생성된 CURP 검증 시스템

### PHP 백엔드
- `app/Services/Mexico/CURPValidator.php` - 핵심 검증 로직
- `app/Livewire/Mexico/CurpInput.php` - Livewire 컴포넌트
- `app/Rules/CurpValid.php` - Laravel 검증 규칙
- `app/Http/Controllers/MexicoValidationController.php` - API 컨트롤러

### 프론트엔드
- `resources/views/livewire/mexico/curp-input.blade.php` - Blade 템플릿
- `resources/js/components/forms/CURPInput.tsx` - React 컴포넌트

### API
- `POST /api/validate-curp` - CURP 검증 엔드포인트

### 다국어 지원
- `lang/es/validation.php` - 스페인어 검증 메시지
- `lang/es/curp.php` - CURP 관련 번역

### 사용 방법
```php
// Laravel에서 사용
$validator = new CURPValidator();
$isValid = $validator->validate('AAAA999999HDFRRR99');
$details = $validator->validateWithDetails('AAAA999999HDFRRR99');

// Livewire에서 사용
<livewire:mexico.curp-input value="" realtime="true" />

// React에서 사용
<CURPInput
  value={curp}
  onChange={(value, data) => handleCurpChange(value, data)}
  required
/>
```
```

사용자의 요청 "$ARGUMENTS"에 따라 CURP 검증 시스템을 구현하고 프로젝트에 통합하세요.