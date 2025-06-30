import { format, parseISO, isToday } from 'date-fns'
import { nl } from "date-fns/locale";
import { cn } from '@/utils'
import { Badge, Avatar, AvatarImage, AvatarFallback, RichText } from "@/base-components"
import { __, getVariant } from '@/stores';

export const NewsItem = ({ newsItem }) => {

  let createdAt = formatDate(newsItem.created_at)

  return (
    <div className="relative z-10 bg-[rgb(241 245 249 / 75%)] flex flex-col items-start rounded-lg p-2 text-left text-sm">
      <div className="flex w-full flex-col">
        <div className="flex items-center">
          <div className="flex items-center">
            <div className="font-semibold"> {`${newsItem.creator?.firstname} ${newsItem.creator?.lastname}`}</div>
          </div>
          <div
            className={cn(
              "ml-auto text-sm",
              newsItem.selected === newsItem.id
                ? "text-foreground"
                : "text-muted-foreground"
            )}
          >
            {/* {createdAt} */}
          </div>
        </div>
      </div>
      <div className="flex w-full p-2">
        <Avatar className="rounded mr-3 top-0 ">
          <AvatarImage src={newsItem.creator?.image_path} alt={newsItem.creator?.firstname} />
          <AvatarFallback>{newsItem.creator?.firstname.charAt(0)}</AvatarFallback>
        </Avatar>
        <div className="relative w-full p-3 py-2 text-sm text-muted-foreground border border-slate-100 rounded border-neutral-200 bg-slate-50
    after:content-[''] after:absolute after:z-10 after:left-[-10px] after:top-[5px] after:w-0 after:h-0 after:border-r-[10px] after:border-r-neutral-200 after:border-t-[10px] after:border-t-transparent after:border-b-[10px] after:border-b-transparent">

          <span className="block">{newsItem?.task_id ? newsItem.task?.name : 'Mededeling'}</span>
          {newsItem.content?.length > 0 &&
            <RichText text={newsItem.content} className='text-gray-700'/>
          }

          {newsItem?.status && (
            <div className="mt-2">
              <span className="block font-medium">Status gewijzigd naar&nbsp;</span>
              <Badge className="mt-1 h-6 py-1 px-2" variant={newsItem.status.name}>
                {__(newsItem.status.name)}
              </Badge>
            </div>
          )}

          <span className="block text-sm mt-1">{createdAt}</span>

        </div>
      </div>

    </div >
  );
}

const formatDate = (createdAt) => {
  const date = parseISO(createdAt);

  if (isToday(date)) {
    return `vandaag ${format(date, "HH:mm", { locale: nl })}`; // "Vandaag HH:mm"
  }

  return format(date, "PP HH:mm", { locale: nl }); // Dutch localized date
};

