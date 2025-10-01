interface Props {
    user: {
        name: string;
        email: string;
    } | null;
}

export default function Orders({ user }: Props) {
    return (
        <div className="min-h-screen bg-base-200 p-4">
            <div className="max-w-4xl mx-auto">
                <div className="card bg-base-100 shadow-xl">
                    <div className="card-body">
                        <h2 className="card-title">내 주문 내역</h2>
                        {user ? (
                            <>
                                <div className="alert alert-success">
                                    <span>로그인 성공! {user.name} ({user.email})</span>
                                </div>
                                <p className="mt-4">주문 내역이 없습니다 (Placeholder)</p>
                            </>
                        ) : (
                            <div className="alert alert-warning">
                                <span>로그인이 필요합니다</span>
                            </div>
                        )}
                    </div>
                </div>
            </div>
        </div>
    );
}
