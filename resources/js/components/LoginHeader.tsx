// no state used in this component
import type { Language } from '@/types/language';

interface LoginHeaderProps {
  onLoginClick: () => void;
  language?: Language;
}

interface LoginTranslations {
  [key: string]: {
    promoText: string;
    loginButton: string;
  };
}

const loginTranslations: LoginTranslations = {
  ko: {
    promoText: '🎁 혜택 폭발! 로그인 필수',
    loginButton: '로그인'
  },
  es: {
    promoText: '🎁 ¡Beneficios top!',
    loginButton: 'Iniciar Sesión'
  },
  en: {
    promoText: '🎁 Explosive rewards!',
    loginButton: 'Login'
  }
};

function LoginButtonContent({ text }: { text: string }) {
  return (
    <div className="content-stretch flex gap-[10px] items-center justify-center relative shrink-0">
      <p className="font-semibold leading-[normal] relative shrink-0 text-[#00b96f] dark:text-[#03D67B] text-[12px] text-nowrap whitespace-pre">
        {text}
      </p>
    </div>
  );
}

function LoginButton({ text, onClick }: { text: string; onClick: () => void }) {
  return (
    <button
      onClick={onClick}
      className="bg-white dark:bg-[#F6F6F6] h-[25px] relative rounded-[20px] shrink-0 w-[110px] hover:bg-[#F6F6F6] dark:hover:bg-white transition-colors active:scale-95"
    >
      <div className="flex flex-row items-center justify-center relative size-full">
        <div className="box-border content-stretch flex gap-[10px] h-[25px] items-center justify-center p-[20px] relative w-[110px] mx-[15px]">
          <LoginButtonContent text={text} />
        </div>
      </div>
    </button>
  );
}

function LoginHeaderContent({ onLoginClick, language = 'ko' }: LoginHeaderProps) {
  const t = loginTranslations[language];

  return (
    <div className="bg-[#00b96f] dark:bg-[#03D67B] h-[50px] relative shrink-0 w-full">
      <div className="flex flex-row items-center overflow-clip relative size-full">
        <div className="box-border content-stretch flex h-[50px] items-center justify-between relative w-full px-[20px] py-[10px]">
          <button
            onClick={() => {
              // TODO: BenefitsModal 구현 시 모달 열기
            }}
            className={`font-bold leading-[normal] relative shrink-0 text-white pr-[15px] hover:opacity-80 transition-opacity cursor-pointer text-left ${
              language === 'ko'
                ? 'text-[0.75rem] sm:text-[0.875rem] md:text-[1rem] lg:text-[1.125rem] xl:text-[1.25rem]'
                : language === 'es'
                ? 'text-[1rem] sm:text-[1.125rem] md:text-[1.3rem] lg:text-[1.5rem] xl:text-[1.625rem]'
                : 'text-[0.85rem] sm:text-[0.95rem] md:text-[1.1rem] lg:text-[1.275rem] xl:text-[1.375rem]'
            } ${language === 'es' ? 'max-[390px]:whitespace-normal max-[390px]:break-words' : 'whitespace-pre'}`}
          >
            {t.promoText}
          </button>
          <LoginButton text={t.loginButton} onClick={onLoginClick} />
        </div>
      </div>
    </div>
  );
}

export function LoginHeader({ onLoginClick, language = 'ko' }: LoginHeaderProps) {
  return (
    <div className="content-stretch flex flex-col items-start relative size-full">
      <LoginHeaderContent onLoginClick={onLoginClick} language={language} />
    </div>
  );
}
