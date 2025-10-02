import { ArrowLeft, MapPin } from 'lucide-react';

interface HeaderProps {
  title: string;
  showBack?: boolean;
  showLocation?: boolean;
  onBack?: () => void;
}

export function Header({ title, showBack = true, showLocation = false, onBack }: HeaderProps) {
  return (
    <header className="bg-[#03D67B] dark:bg-[#00B96F] sticky top-0 z-50 w-full">
      <div className="flex items-center justify-between p-[min(5vw,1rem)] h-[calc(3.5rem+1vw)]">
        <div className="flex items-center gap-[min(3vw,1rem)]">
          {showBack && (
            <button
              onClick={onBack}
              className="text-white hover:bg-white/20 p-2 rounded-md transition-colors"
            >
              <ArrowLeft className="h-5 w-5" />
            </button>
          )}
          <h1 className="text-white font-bold text-[calc(1rem+0.5vw)] tracking-tight px-[20px] py-[0px]">
            {title}
          </h1>
        </div>
        {showLocation && (
          <button className="text-white hover:bg-white/20 p-2 rounded-md transition-colors">
            <MapPin className="h-5 w-5" />
          </button>
        )}
      </div>
    </header>
  );
}
