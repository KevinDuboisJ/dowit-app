import {
  Accordion,
  AccordionTrigger,
  AccordionItem,
  AccordionContent,
} from "@/base-components";

const Help = () => {

  return (
    <>
      <div className="h-full fadeInUp box pt-8 px-5 pb-24 flex flex-col items-center h-[550px]">
        {/* BEGIN: Help Title */}
        <div className="text-center">
          <div className="text-4xl font-bold mt-5">
            Veel Gestelde Vragen
          </div>
          <div className="text-base text-slate-500 mt-3">
            Hieronder vindt u antwoorden op vragen die we het vaakst krijgen over Dowit.
          </div>
          <a href="mailto:kevin.dubois@azmonica.be" className="text-primary text-base">
            Heb je nog steeds geen idee? Stuur ons een e-mail.
          </a>
        </div>
        {/* END: Help Title */}

        {/* BEGIN: Help Content */}
        <Accordion type="single" collapsible className="accordion-boxed md:w-5/6 mt-16">
          <AccordionItem value="item-1">
            <AccordionTrigger className="text-md font-semibold text-slate-900 dark:text-slate-50" >
              Hoe kan ik een taak maken die zich elke x dagen zelf triggert?
            </AccordionTrigger>
            <AccordionContent className="text-slate-600 dark:text-slate-500 leading-relaxed">
              Een taakplanner kan alleen worden aangemaakt door gebruikers met de rol admin
            </AccordionContent>
          </AccordionItem>

          {/* You can add more AccordionItems for more FAQ questions */}
          <AccordionItem value="item-2">
            <AccordionTrigger className="text-md font-semibold text-slate-900 dark:text-slate-50">
              Wat zijn de vereisten voor Dowit?
            </AccordionTrigger>
            <AccordionContent className="text-slate-600 dark:text-slate-500 leading-relaxed">
              Dowit vereist een actieve internetverbinding en een moderne browser voor de beste ervaring
            </AccordionContent>
          </AccordionItem>

        </Accordion>
        {/* END: Help Content */}
      </div>
    </>
  )
}

export default Help;
